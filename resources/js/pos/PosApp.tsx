import { useCallback, useEffect, useMemo, useRef, useState } from "react";
import {
  QueryClient,
  QueryClientProvider,
  useMutation,
  useQuery,
  useQueryClient,
} from "@tanstack/react-query";
import { Store } from "lucide-react";
import { Toaster, toast } from "sonner";
import { getErrorMessage, posApi } from "@pos/api";
import { PosCatalogo } from "@pos/components/PosCatalogo";
import { PosTicket } from "@pos/components/PosTicket";
import { TicketModal } from "@pos/components/TicketModal";
import { VentaLibreModal } from "@pos/components/VentaLibreModal";
import { round2, type PosCartItem } from "@pos/types";
import type {
  MetodoPago,
  PosAiCartItem,
  PosAiCartResponse,
  PosCheckoutPayload,
  PosCheckoutResponse,
  Producto,
} from "@pos/types";

export interface PosAppProps {
  businessName: string;
  currency: string;
  homeUrl: string;
}

const queryClient = new QueryClient({
  defaultOptions: {
    queries: {
      retry: 1,
      refetchOnWindowFocus: false,
    },
  },
});

const POS_QUERY_KEYS = {
  catalogo: ["pos", "catalogo"] as const,
  clientes: ["pos", "clientes"] as const,
};

function PosWorkspace({ businessName, currency, homeUrl }: PosAppProps) {
  const qc = useQueryClient();
  const moneda = currency || "PEN";

  const [cartItems, setCartItems] = useState<PosCartItem[]>([]);
  const [clienteId, setClienteId] = useState<number | null>(null);
  const [metodoPago, setMetodoPago] = useState<MetodoPago>("EFECTIVO");
  const [montoRecibidoStr, setMontoRecibidoStr] = useState("");
  const [recentlyAddedId, setRecentlyAddedId] = useState<number | null>(null);
  const [ventaLibreOpen, setVentaLibreOpen] = useState(false);
  const [checkoutResult, setCheckoutResult] = useState<PosCheckoutResponse | null>(null);
  const [pendingAiProposal, setPendingAiProposal] = useState<PosAiCartResponse | null>(null);

  const searchInputRef = useRef<HTMLInputElement | null>(null);
  const addedTimerRef = useRef<number | null>(null);
  const lastOriginRef = useRef<"MANUAL" | "VOZ" | "IA">("MANUAL");
  const voiceActionsRef = useRef<{
    speak: (texto: string) => void;
    isHandsFreeMode: boolean;
  }>({
    speak: () => undefined,
    isHandsFreeMode: false,
  });

  const catalogoQuery = useQuery({
    queryKey: POS_QUERY_KEYS.catalogo,
    queryFn: () => posApi.getCatalogo(),
  });

  const clientesQuery = useQuery({
    queryKey: POS_QUERY_KEYS.clientes,
    queryFn: () => posApi.getClientes(),
  });

  const productos = catalogoQuery.data?.productos ?? [];
  const categorias = catalogoQuery.data?.categorias ?? [];
  const clientes = clientesQuery.data ?? [];

  const cartQuantities = useMemo(() => {
    const map: Record<number, number> = {};
    for (const item of cartItems) {
      if (!item.esVentaLibre && item.productoId !== undefined) {
        map[item.productoId] = (map[item.productoId] ?? 0) + item.cantidad;
      }
    }
    return map;
  }, [cartItems]);

  const totalCart = useMemo(
    () =>
      round2(
        cartItems.reduce((acc, it) => acc + it.cantidad * it.precioUnitario, 0),
      ),
    [cartItems],
  );

  const triggerAddedFeedback = useCallback((productoId: number) => {
    if (addedTimerRef.current !== null) {
      window.clearTimeout(addedTimerRef.current);
    }
    setRecentlyAddedId(productoId);
    addedTimerRef.current = window.setTimeout(() => {
      setRecentlyAddedId(null);
      addedTimerRef.current = null;
    }, 320);
  }, []);

  useEffect(() => {
    return () => {
      if (addedTimerRef.current !== null) {
        window.clearTimeout(addedTimerRef.current);
      }
    };
  }, []);

  const handleAddProduct = useCallback(
    (producto: Producto) => {
      if (producto.stockActual <= 0) {
        toast.error(`${producto.nombre} está agotado`);
        return;
      }

      setCartItems((prev) => {
        const key = `prod-${producto.id}`;
        const existing = prev.find((it) => it.key === key);
        if (existing) {
          if (existing.cantidad + 1 > producto.stockActual) {
            toast.warning(
              `Stock máximo alcanzado para ${producto.nombre} (${producto.stockActual} ${producto.unidadMedida})`,
            );
            return prev;
          }
          return prev.map((it) =>
            it.key === key
              ? { ...it, cantidad: round2(it.cantidad + 1), stockActual: producto.stockActual }
              : it,
          );
        }

        return [
          ...prev,
          {
            key,
            productoId: producto.id,
            nombreProducto: producto.nombre,
            codigoSku: producto.codigoSku,
            categoria: producto.categoria,
            precioUnitario: producto.precioVenta,
            cantidad: 1,
            stockActual: producto.stockActual,
            unidadMedida: producto.unidadMedida,
            esVentaLibre: false,
          },
        ];
      });

      triggerAddedFeedback(producto.id);
    },
    [triggerAddedFeedback],
  );

  const handleAddVentaLibre = useCallback(
    (item: { nombreProducto: string; precioUnitario: number; cantidad: number }) => {
      const key = `libre-${Date.now()}-${Math.random().toString(36).slice(2, 6)}`;
      setCartItems((prev) => [
        ...prev,
        {
          key,
          nombreProducto: item.nombreProducto,
          precioUnitario: item.precioUnitario,
          cantidad: item.cantidad,
          esVentaLibre: true,
        },
      ]);
      toast.success(`"${item.nombreProducto}" agregado al carrito`);
    },
    [],
  );

  const handleAddAiItems = useCallback(
    (
      aiItems: PosAiCartItem[],
      metodoPagoDetectado?: MetodoPago | null,
      origin: "VOZ" | "IA" = "IA",
    ) => {
      if (aiItems.length === 0) return;

      lastOriginRef.current = origin;
      const stockWarnings: string[] = [];

      setCartItems((prev) => {
        const next = [...prev];

        for (const aiItem of aiItems) {
          const catalogProd = productos.find((p) => p.id === aiItem.productoId);
          const maxStock = catalogProd ? catalogProd.stockActual : aiItem.stockActual;

          if (maxStock <= 0) {
            stockWarnings.push(`${aiItem.nombreProducto} está agotado`);
            continue;
          }

          const key = `prod-${aiItem.productoId}`;
          const existingIdx = next.findIndex((it) => it.key === key);

          if (existingIdx >= 0) {
            const existing = next[existingIdx];
            const desiredQty = round2(existing.cantidad + aiItem.cantidad);
            const finalQty = Math.min(desiredQty, maxStock);
            if (desiredQty > maxStock) {
              stockWarnings.push(
                `${aiItem.nombreProducto} ajustado al stock disponible (${maxStock})`,
              );
            }
            next[existingIdx] = {
              ...existing,
              cantidad: finalQty,
              stockActual: maxStock,
            };
          } else {
            const finalQty = Math.min(aiItem.cantidad, maxStock);
            if (aiItem.cantidad > maxStock) {
              stockWarnings.push(
                `${aiItem.nombreProducto} ajustado al stock disponible (${maxStock})`,
              );
            }
            next.push({
              key,
              productoId: aiItem.productoId,
              nombreProducto: aiItem.nombreProducto,
              codigoSku: aiItem.codigoSku,
              categoria: aiItem.categoria,
              precioUnitario: aiItem.precioUnitario,
              cantidad: finalQty,
              stockActual: maxStock,
              unidadMedida: aiItem.unidadMedida,
              esVentaLibre: false,
            });
          }
        }

        return next;
      });

      if (metodoPagoDetectado) {
        setMetodoPago(metodoPagoDetectado);
      }

      if (stockWarnings.length > 0) {
        toast.warning(stockWarnings.join(" · "));
      }
    },
    [productos],
  );

  const handleIncrementItem = useCallback((key: string) => {
    setCartItems((prev) =>
      prev.map((it) => {
        if (it.key !== key) return it;
        if (!it.esVentaLibre && it.stockActual !== undefined && it.cantidad + 1 > it.stockActual) {
          toast.warning(`Stock máximo disponible: ${it.stockActual}`);
          return it;
        }
        return { ...it, cantidad: round2(it.cantidad + 1) };
      }),
    );
  }, []);

  const handleDecrementItem = useCallback((key: string) => {
    setCartItems((prev) =>
      prev.flatMap((it) => {
        if (it.key !== key) return [it];
        const nextQty = round2(it.cantidad - 1);
        return nextQty <= 0 ? [] : [{ ...it, cantidad: nextQty }];
      }),
    );
  }, []);

  const handleRemoveItem = useCallback((key: string) => {
    setCartItems((prev) => prev.filter((it) => it.key !== key));
  }, []);

  const handleClearCart = useCallback(() => {
    setCartItems([]);
    setPendingAiProposal(null);
    setMontoRecibidoStr("");
    lastOriginRef.current = "MANUAL";
  }, []);

  const checkoutMutation = useMutation({
    mutationFn: (payload: PosCheckoutPayload) => posApi.checkout(payload),
    onSuccess: async (res) => {
      setCheckoutResult(res);
      setCartItems([]);
      setMontoRecibidoStr("");
      setPendingAiProposal(null);
      setClienteId(null);
      setMetodoPago("EFECTIVO");
      lastOriginRef.current = "MANUAL";

      await Promise.all([
        qc.invalidateQueries({ queryKey: POS_QUERY_KEYS.catalogo }),
        qc.invalidateQueries({ queryKey: POS_QUERY_KEYS.clientes }),
      ]);

      if (voiceActionsRef.current.isHandsFreeMode && res.mensaje_voz) {
        voiceActionsRef.current.speak(res.mensaje_voz);
      }
    },
    onError: (err) => {
      toast.error(getErrorMessage(err, "No se pudo completar el cobro"));
    },
  });

  const handleCheckout = useCallback(() => {
    if (cartItems.length === 0 || checkoutMutation.isPending) return;

    if (metodoPago === "FIADO" && !clienteId) {
      toast.error("Selecciona un cliente para registrar una venta al fiado");
      return;
    }

    let montoRecibido = totalCart;
    if (metodoPago === "EFECTIVO" && montoRecibidoStr.trim() !== "") {
      const parsed = Number(montoRecibidoStr);
      if (!Number.isFinite(parsed) || parsed < totalCart) {
        toast.error("El monto recibido en efectivo es menor al total a cobrar");
        return;
      }
      montoRecibido = round2(parsed);
    }

    const clienteSeleccionado = clienteId
      ? clientes.find((c) => c.id === clienteId)
      : undefined;

    const payload: PosCheckoutPayload = {
      items: cartItems.map((it) => ({
        ...(it.productoId !== undefined ? { productoId: it.productoId } : {}),
        nombreProducto: it.nombreProducto,
        cantidad: it.cantidad,
        precioUnitario: it.precioUnitario,
      })),
      metodoPago,
      clienteId: clienteId ?? null,
      clienteNombre: clienteSeleccionado?.nombre ?? null,
      montoRecibido,
      origen: lastOriginRef.current,
    };

    checkoutMutation.mutate(payload);
  }, [
    cartItems,
    checkoutMutation,
    metodoPago,
    clienteId,
    totalCart,
    montoRecibidoStr,
    clientes,
  ]);

  const handleNewSale = useCallback(() => {
    setCheckoutResult(null);
    window.setTimeout(() => {
      searchInputRef.current?.focus();
    }, 50);
  }, []);

  const handleRegisterVoiceActions = useCallback(
    (actions: { speak: (texto: string) => void; isHandsFreeMode: boolean }) => {
      voiceActionsRef.current = actions;
    },
    [],
  );

  // Atajos globales de teclado en Caja POS: F2 buscar, F4 cobrar, ESC vaciar carrito / cerrar
  useEffect(() => {
    const onKeyDown = (e: KeyboardEvent) => {
      if (e.key === "F2") {
        e.preventDefault();
        searchInputRef.current?.focus();
        searchInputRef.current?.select();
        return;
      }

      if (e.key === "F4") {
        e.preventDefault();
        if (!checkoutResult && !ventaLibreOpen && cartItems.length > 0 && !checkoutMutation.isPending) {
          handleCheckout();
        }
        return;
      }

      if (e.key === "Escape") {
        if (checkoutResult || ventaLibreOpen) {
          return;
        }
        if (pendingAiProposal) {
          setPendingAiProposal(null);
          return;
        }
        if (cartItems.length > 0) {
          handleClearCart();
          toast.info("Carrito vaciado");
        }
      }
    };

    window.addEventListener("keydown", onKeyDown);
    return () => window.removeEventListener("keydown", onKeyDown);
  }, [
    cartItems.length,
    checkoutMutation.isPending,
    checkoutResult,
    handleCheckout,
    handleClearCart,
    pendingAiProposal,
    ventaLibreOpen,
  ]);

  return (
    <div className="flex min-h-screen flex-col bg-[#F4EFE4] text-[#192315] lg:h-screen lg:overflow-hidden">
      {/* Cabecera superior limpia del POS */}
      <header className="pos-no-print flex h-14 shrink-0 items-center justify-between gap-4 border-b border-[#192315]/14 bg-[#FAF7EF] px-4 lg:px-6">
        <div className="flex min-w-0 items-center gap-3">
          <div className="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-[#192315] text-[#F4EFE4] shadow-xs">
            <Store className="h-4.5 w-4.5" />
          </div>
          <div className="min-w-0">
            <h1 className="font-editorial truncate text-base font-semibold tracking-tight text-[#192315] sm:text-lg">
              Punto de Venta / Caja
            </h1>
            <p className="truncate text-[11px] font-medium text-[#595548]">
              {businessName}
            </p>
          </div>
        </div>

        <a
          href={homeUrl}
          className="inline-flex min-h-9 shrink-0 items-center gap-1.5 rounded-xl border border-[#192315]/22 bg-white px-3 py-1.5 text-xs font-semibold text-[#192315] transition-colors hover:border-[#192315] hover:bg-[#FAF7EF]"
        >
          <span>← Volver a la tienda</span>
        </a>
      </header>

      {/* Workspace 65% / 35% */}
      <main className="flex-1 min-h-0 p-3 lg:p-4">
        <div className="flex h-full flex-col gap-3 lg:flex-row lg:gap-4">
          {/* Columna izquierda (65%): Catálogo táctil estilo Metro */}
          <div className="min-h-105 lg:h-full lg:w-[65%] lg:min-h-0">
            <PosCatalogo
              productos={productos}
              categorias={categorias}
              isLoading={catalogoQuery.isLoading}
              isError={catalogoQuery.isError}
              onRetry={() => catalogoQuery.refetch()}
              moneda={moneda}
              cartQuantities={cartQuantities}
              recentlyAddedId={recentlyAddedId}
              searchInputRef={searchInputRef}
              onAddProduct={handleAddProduct}
              onOpenVentaLibre={() => setVentaLibreOpen(true)}
            />
          </div>

          {/* Columna derecha (35%): Barra IA + Voz, Carrito y Cobro */}
          <div className="min-h-125 lg:h-full lg:w-[35%] lg:min-h-0">
            <PosTicket
              items={cartItems}
              clientes={clientes}
              moneda={moneda}
              clienteId={clienteId}
              onClienteChange={setClienteId}
              metodoPago={metodoPago}
              onMetodoPagoChange={setMetodoPago}
              montoRecibidoStr={montoRecibidoStr}
              onMontoRecibidoChange={setMontoRecibidoStr}
              onIncrementItem={handleIncrementItem}
              onDecrementItem={handleDecrementItem}
              onRemoveItem={handleRemoveItem}
              onClearCart={handleClearCart}
              onAddAiItems={handleAddAiItems}
              onCheckout={handleCheckout}
              isCheckingOut={checkoutMutation.isPending}
              pendingAiProposal={pendingAiProposal}
              onPendingAiProposalChange={setPendingAiProposal}
              onRegisterVoiceActions={handleRegisterVoiceActions}
            />
          </div>

          <VentaLibreModal
            open={ventaLibreOpen}
            onOpenChange={setVentaLibreOpen}
            moneda={moneda}
            onAdd={handleAddVentaLibre}
          />

          <TicketModal
            open={checkoutResult !== null}
            checkoutResult={checkoutResult}
            businessName={businessName}
            moneda={moneda}
            onNewSale={handleNewSale}
          />
        </div>
      </main>
    </div>
  );
}

export function PosApp(props: PosAppProps) {
  return (
    <QueryClientProvider client={queryClient}>
      <PosWorkspace {...props} />
      <Toaster richColors position="top-right" />
    </QueryClientProvider>
  );
}
