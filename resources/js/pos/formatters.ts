import type { MetodoPago } from "@pos/types";

const SIMBOLOS_MONEDA: Record<string, string> = {
  PEN: "S/",
  USD: "$",
  MXN: "$",
  COP: "$",
  CLP: "$",
  ARS: "$",
  EUR: "€",
};

export function formatMoney(amount: number, moneda = "PEN"): string {
  const safe = Number.isFinite(amount) ? amount : 0;
  const code = (moneda || "PEN").toUpperCase();
  const simbolo = SIMBOLOS_MONEDA[code] ?? `${code} `;
  const formatted = new Intl.NumberFormat("es-PE", {
    minimumFractionDigits: 2,
    maximumFractionDigits: 2,
  }).format(Math.abs(safe));
  const prefix = safe < 0 ? "-" : "";
  return `${prefix}${simbolo} ${formatted}`;
}

export function formatNumber(value: number, maxDecimals = 2): string {
  const safe = Number.isFinite(value) ? value : 0;
  return new Intl.NumberFormat("es-PE", {
    minimumFractionDigits: 0,
    maximumFractionDigits: maxDecimals,
  }).format(safe);
}

function parseDateSafe(iso: string | Date): Date {
  if (iso instanceof Date) return iso;
  if (/^\d{4}-\d{2}-\d{2}$/.test(iso)) {
    const [y, m, d] = iso.split("-").map(Number);
    return new Date(y, m - 1, d);
  }
  return new Date(iso);
}

export function formatDateTime(iso: string | Date): string {
  const date = parseDateSafe(iso);
  if (Number.isNaN(date.getTime())) return "—";
  return new Intl.DateTimeFormat("es-PE", {
    day: "2-digit",
    month: "short",
    year: "numeric",
    hour: "2-digit",
    minute: "2-digit",
  }).format(date);
}

export const METODO_PAGO_LABELS: Record<MetodoPago, string> = {
  EFECTIVO: "Efectivo",
  YAPE: "Yape",
  PLIN: "Plin",
  TRANSFERENCIA: "Transferencia",
  TARJETA: "Tarjeta",
  FIADO: "Fiado",
};
