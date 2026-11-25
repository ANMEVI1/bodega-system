import type {
  Cliente,
  PosAiCartResponse,
  PosCatalogoData,
  PosCheckoutPayload,
  PosCheckoutResponse,
} from "@pos/types";

interface ApiErrorShape {
  error?: {
    code?: string;
    message?: string;
  };
  message?: string;
  errors?: Record<string, string[]>;
}

function getCsrfToken(): string {
  if (typeof document === "undefined") return "";
  const meta = document.querySelector('meta[name="csrf-token"]');
  return meta?.getAttribute("content") ?? "";
}

async function requestJson<T>(url: string, init: RequestInit = {}): Promise<T> {
  const headers = new Headers(init.headers);
  headers.set("Accept", "application/json");
  headers.set("X-Requested-With", "XMLHttpRequest");

  const csrfToken = getCsrfToken();
  if (csrfToken) {
    headers.set("X-CSRF-TOKEN", csrfToken);
  }

  if (init.body && !headers.has("Content-Type")) {
    headers.set("Content-Type", "application/json");
  }

  const response = await fetch(url, {
    credentials: "same-origin",
    ...init,
    headers,
  });

  const text = await response.text();
  let json: (ApiErrorShape & { data?: T }) | null = null;
  if (text) {
    try {
      json = JSON.parse(text) as ApiErrorShape & { data?: T };
    } catch {
      json = null;
    }
  }

  if (!response.ok) {
    const firstValidationMsg = json?.errors
      ? Object.values(json.errors).flat()[0]
      : undefined;
    const message =
      json?.error?.message ||
      firstValidationMsg ||
      json?.message ||
      `Error ${response.status} al comunicarse con el servidor`;
    throw new Error(message);
  }

  if (!json || json.data === undefined) {
    throw new Error("Respuesta inválida del servidor POS");
  }

  return json.data;
}

export function getErrorMessage(err: unknown, fallback = "Ocurrió un error"): string {
  if (err instanceof Error && err.message.trim() !== "") {
    return err.message;
  }
  return fallback;
}

export const posApi = {
  getCatalogo(): Promise<PosCatalogoData> {
    return requestJson<PosCatalogoData>("/api/pos/catalogo");
  },

  getClientes(): Promise<Cliente[]> {
    return requestJson<Cliente[]>("/api/pos/clientes");
  },

  aiCart(mensaje: string): Promise<PosAiCartResponse> {
    return requestJson<PosAiCartResponse>("/api/pos/ai-cart", {
      method: "POST",
      body: JSON.stringify({ mensaje }),
    });
  },

  checkout(payload: PosCheckoutPayload): Promise<PosCheckoutResponse> {
    return requestJson<PosCheckoutResponse>("/api/pos/checkout", {
      method: "POST",
      body: JSON.stringify(payload),
    });
  },
};
