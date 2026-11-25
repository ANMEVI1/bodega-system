import { useEffect, useMemo, useRef, useState, type FormEvent } from "react";
import { useMutation } from "@tanstack/react-query";
import {
  AlertTriangle,
  Banknote,
  Building2,
  Check,
  CreditCard,
  Headphones,
  Loader2,
  Mic,
  MicOff,
  Minus,
  Plus,
  ShoppingCart,
  Smartphone,
  Sparkles,
  Trash2,
  UserCheck,
  Volume2,
  VolumeX,
  X,
} from "lucide-react";
import { toast } from "sonner";
import { getErrorMessage, posApi } from "@pos/api";
import { Badge } from "@pos/components/ui/Badge";
import { Button } from "@pos/components/ui/Button";
import { cn } from "@pos/components/ui/cn";
import { Select } from "@pos/components/ui/Select";
import { round2, type PosCartItem } from "@pos/types";
import { formatMoney, formatNumber } from "@pos/formatters";
import {
  esCancelacionVoz,
  esConfirmacionVoz,
  useVoiceAssistant,
} from "@pos/hooks/useVoiceAssistant";
import type {
  Cliente,
  MetodoPago,
  PosAiCartItem,
  PosAiCartResponse,
} from "@pos/types";

interface PosTicketProps {
  items: PosCartItem[];
  clientes: Cliente[];
  moneda: string;
  clienteId: number | null;
  onClienteChange: (id: number | null) => void;
  metodoPago: MetodoPago;
  onMetodoPagoChange: (metodo: MetodoPago) => void;
  montoRecibidoStr: string;
  onMontoRecibidoChange: (val: string) => void;
  onIncrementItem: (key: string) => void;
  onDecrementItem: (key: string) => void;
  onRemoveItem: (key: string) => void;
  onClearCart: () => void;
  onAddAiItems: (
    aiItems: PosAiCartItem[],
    metodoPagoDetectado?: MetodoPago | null,
    origin?: "VOZ" | "IA",
  ) => void;
  onCheckout: () => void;
  isCheckingOut: boolean;
  pendingAiProposal: PosAiCartResponse | null;
  onPendingAiProposalChange: (proposal: PosAiCartResponse | null) => void;
  onRegisterVoiceActions?: (actions: {
    speak: (texto: string) => void;
    isHandsFreeMode: boolean;
  }) => void;
}

const METODOS_POS: Array<{
  value: MetodoPago;
  label: string;
  icon: typeof Banknote;
}> = [
  { value: "EFECTIVO", label: "Efectivo", icon: Banknote },
  { value: "YAPE", label: "Yape", icon: Smartphone },
  { value: "PLIN", label: "Plin", icon: Smartphone },
  { value: "TRANSFERENCIA", label: "Transf.", icon: Building2 },
  { value: "FIADO", label: "Fiado", icon: UserCheck },
];

const BILLETES_RAPIDOS = [10, 20, 50, 100];

export function PosTicket({
  items,
  clientes,
  moneda,
  clienteId,
  onClienteChange,
  metodoPago,
  onMetodoPagoChange,
  montoRecibidoStr,
  onMontoRecibidoChange,
  onIncrementItem,
  onDecrementItem,
  onRemoveItem,
  onClearCart,
  onAddAiItems,
  onCheckout,
  isCheckingOut,
  pendingAiProposal,
  onPendingAiProposalChange,
  onRegisterVoiceActions,
}: PosTicketProps) {
  const [aiInput, setAiInput] = useState("");
  const lastInputWasVoiceRef = useRef(false);
  const pendingProposalRef = useRef<PosAiCartResponse | null>(pendingAiProposal);
  const isHandsFreeRef = useRef(false);

  useEffect(() => {
    pendingProposalRef.current = pendingAiProposal;
  }, [pendingAiProposal]);

  const total = useMemo(
    () =>
      round2(
        items.reduce((acc, it) => acc + it.cantidad * it.precioUnitario, 0),
      ),
    [items],
  );

  const totalUnidades = useMemo(
    () => round2(items.reduce((acc, it) => acc + it.cantidad, 0)),
    [items],
  );

  const montoRecibidoNum = useMemo(() => {
    if (!montoRecibidoStr.trim()) return null;
    const parsed = Number(montoRecibidoStr);
    return Number.isFinite(parsed) ? parsed : null;
  }, [montoRecibidoStr]);

  const faltaEfectivo =
    metodoPago === "EFECTIVO" &&
    montoRecibidoNum !== null &&
    montoRecibidoNum < total;

  const vuelto =
    metodoPago === "EFECTIVO" && montoRecibidoNum !== null && montoRecibidoNum >= total
      ? round2(montoRecibidoNum - total)
      : 0;

  const fiadoSinCliente = metodoPago === "FIADO" && !clienteId;

  const clienteOptions = useMemo(
    () => [
      { value: "general", label: "Cliente General / Mostrador" },
      ...clientes.map((c) => ({
        value: String(c.id),
        label:
          c.saldoPendiente > 0
            ? `${c.nombre} (Debe ${formatMoney(c.saldoPendiente, moneda)})`
            : c.nombre,
      })),
    ],
    [clientes, moneda],
  );

  const voiceBridgeRef = useRef<{
    speak: (texto: string, opts?: { autoListenAfter?: boolean; forceAutoListen?: boolean }) => void;
    stopListening: () => void;
    stopSpeaking: () => void;
    isHandsFreeMode: boolean;
  }>({
    speak: () => undefined,
    stopListening: () => undefined,
    stopSpeaking: () => undefined,
    isHandsFreeMode: false,
  });

  const aiCartMutation = useMutation({
    mutationFn: (mensaje: string) => posApi.aiCart(mensaje),
    onSuccess: (res) => {
      const usarConfirmacionVoz =
        (lastInputWasVoiceRef.current || isHandsFreeRef.current) &&
        res.requiere_confirmacion &&
        res.items.length > 0;

      if (res.noEncontrados.length > 0) {
        toast.warning(`No encontrados: ${res.noEncontrados.join(", ")}`);
      }
      if (res.advertencias.length > 0) {
        toast.warning(res.advertencias[0]);
      }

      if (usarConfirmacionVoz) {
        onPendingAiProposalChange(res);
        const mensajeParaHablar = res.mensaje_voz?.trim() || res.mensaje;
        voiceBridgeRef.current.speak(mensajeParaHablar, {
          autoListenAfter: true,
          forceAutoListen: true,
        });
        return;
      }

      if (res.items.length > 0) {
        onAddAiItems(
          res.items,
          res.metodoPagoDetectado,
          lastInputWasVoiceRef.current ? "VOZ" : "IA",
        );
        onPendingAiProposalChange(null);
        if (lastInputWasVoiceRef.current || isHandsFreeRef.current) {
          voiceBridgeRef.current.speak(
            res.mensaje_voz?.trim() || "Listo, agregados al carrito",
            { autoListenAfter: false },
          );
        } else {
          toast.success(res.mensaje || "Productos agregados al carrito");
        }
      } else {
        const msgSinItems = res.mensaje || "No encontré productos coincidentes en tu catálogo.";
        toast.info(msgSinItems);
        if (lastInputWasVoiceRef.current || isHandsFreeRef.current) {
          voiceBridgeRef.current.speak(res.mensaje_voz?.trim() || msgSinItems, {
            autoListenAfter: false,
          });
        }
      }
    },
    onError: (err) => {
      const msg = getErrorMessage(err, "No se pudo interpretar el pedido con IA");
      toast.error(msg);
      if (lastInputWasVoiceRef.current || isHandsFreeRef.current) {
        voiceBridgeRef.current.speak(msg, { autoListenAfter: false });
      }
    },
  });

  const confirmPendingProposal = (viaVoiceOrHandsFree: boolean) => {
    const propuesta = pendingProposalRef.current;
    if (!propuesta) return;

    onAddAiItems(
      propuesta.items,
      propuesta.metodoPagoDetectado,
      viaVoiceOrHandsFree || lastInputWasVoiceRef.current ? "VOZ" : "IA",
    );
    onPendingAiProposalChange(null);

    if (viaVoiceOrHandsFree || isHandsFreeRef.current || lastInputWasVoiceRef.current) {
      voiceBridgeRef.current.speak("Listo, agregados al carrito", {
        autoListenAfter: false,
      });
    } else {
      toast.success("Productos agregados al carrito");
    }
  };

  const cancelPendingProposal = (viaVoiceOrHandsFree: boolean) => {
    onPendingAiProposalChange(null);
    if (viaVoiceOrHandsFree || isHandsFreeRef.current) {
      voiceBridgeRef.current.speak("Entendido, cancelado", {
        autoListenAfter: false,
      });
    }
  };

  const {
    isSupported: voiceSupported,
    ttsSupported,
    isListening,
    isSpeaking,
    isHandsFreeMode,
    toggleHandsFreeMode,
    transcript,
    error: voiceError,
    clearError,
    startListening,
    stopListening,
    speak,
    stopSpeaking,
  } = useVoiceAssistant({
    lang: "es-PE",
    onTranscriptReady: (textoFinal) => {
      const limpio = textoFinal.trim();
      if (!limpio || aiCartMutation.isPending) return;

      const propuesta = pendingProposalRef.current;
      if (propuesta) {
        if (esConfirmacionVoz(limpio)) {
          confirmPendingProposal(true);
          return;
        }
        if (esCancelacionVoz(limpio)) {
          cancelPendingProposal(true);
          return;
        }
      }

      lastInputWasVoiceRef.current = true;
      setAiInput("");
      aiCartMutation.mutate(limpio);
    },
  });

  useEffect(() => {
    isHandsFreeRef.current = isHandsFreeMode;
    voiceBridgeRef.current = {
      speak,
      stopListening,
      stopSpeaking,
      isHandsFreeMode,
    };
    onRegisterVoiceActions?.({
      speak: (texto: string) => speak(texto, { autoListenAfter: false }),
      isHandsFreeMode,
    });
  }, [isHandsFreeMode, speak, stopListening, stopSpeaking, onRegisterVoiceActions]);

  useEffect(() => {
    if (voiceError) {
      toast.error(voiceError);
      clearError();
    }
  }, [voiceError, clearError]);

  const handleAiSubmit = (e: FormEvent) => {
    e.preventDefault();
    const texto = aiInput.trim();
    if (!texto || aiCartMutation.isPending) return;

    if (isListening) {
      stopListening();
    }
    lastInputWasVoiceRef.current = false;
    setAiInput("");
    aiCartMutation.mutate(texto);
  };

  return (
    <aside className="flex h-full flex-col overflow-hidden rounded-2xl border border-[#192315]/16 bg-[#FAF7EF] shadow-xs">
      {/* Barra rápida IA + Voz Manos Libres */}
      <div className="border-b border-[#192315]/14 bg-[#A44A28]/8 p-3 space-y-2">
        <div className="flex items-center justify-between gap-2">
          <div className="flex items-center gap-1.5 text-xs font-bold text-[#A44A28]">
            <Sparkles className="h-3.5 w-3.5 text-[#A44A28]" />
            <span>Pedido rápido con IA y Voz</span>
          </div>

          {ttsSupported && (
            <button
              type="button"
              onClick={() => {
                toggleHandsFreeMode();
                if (!isHandsFreeMode) {
                  toast.success("Modo Manos Libres activo en caja");
                }
              }}
              aria-pressed={isHandsFreeMode}
              title={
                isHandsFreeMode
                  ? "Desactivar Manos Libres"
                  : "Activar Manos Libres (confirmación por voz)"
              }
              className={cn(
                "inline-flex items-center gap-1 rounded-full border px-2.5 py-1 text-[11px] font-bold transition-colors cursor-pointer",
                isHandsFreeMode
                  ? "border-[#A44A28] bg-[#A44A28] text-[#FAF7EF] shadow-xs"
                  : "border-[#192315]/20 bg-white text-[#595548] hover:border-[#192315]/35 hover:text-[#192315]",
              )}
            >
              <Headphones className="h-3 w-3" />
              <span>Manos Libres</span>
              {isHandsFreeMode ? (
                <Volume2 className="h-3 w-3" />
              ) : (
                <VolumeX className="h-3 w-3 text-[#595548]/70" />
              )}
            </button>
          )}
        </div>

        <form onSubmit={handleAiSubmit} className="flex items-center gap-1.5">
          <button
            type="button"
            onClick={() => {
              if (!voiceSupported) {
                toast.info("Tu navegador no soporta dictado por voz. Usa Chrome o Edge.");
                return;
              }
              if (isListening) {
                stopListening();
              } else {
                startListening();
              }
            }}
            aria-label={isListening ? "Detener micrófono" : "Dictar productos por voz"}
            title={
              !voiceSupported
                ? "Dictado por voz no disponible"
                : isListening
                  ? "Detener micrófono"
                  : "Dictar pedido"
            }
            className={cn(
              "relative flex h-10 w-10 shrink-0 items-center justify-center rounded-xl border transition-colors cursor-pointer",
              isListening
                ? "border-[#9F2B2B] bg-[#9F2B2B] text-white"
                : voiceSupported
                  ? "border-[#A44A28]/30 bg-white text-[#A44A28] hover:bg-[#F8EFE9]"
                  : "border-[#192315]/14 bg-[#EAE3D2]/60 text-[#595548]/45",
            )}
          >
            {isListening && (
              <span className="absolute inset-0 animate-ping rounded-xl bg-[#9F2B2B]/35 pointer-events-none" />
            )}
            {isListening ? <MicOff className="relative h-4.5 w-4.5" /> : <Mic className="h-4.5 w-4.5" />}
          </button>

          <div className="relative flex-1">
            <Sparkles className="pointer-events-none absolute left-2.5 top-1/2 h-3.5 w-3.5 -translate-y-1/2 text-[#A44A28]/70" />
            <input
              id="pos-ai-input"
              aria-label="Agregar productos con IA"
              type="text"
              value={isListening && transcript ? transcript : aiInput}
              onChange={(e) => setAiInput(e.target.value)}
              placeholder="Ej: agrega 2 arroz y 1 aceite..."
              className="h-10 w-full rounded-xl border border-[#192315]/20 bg-white pl-8 pr-3 text-xs font-medium text-[#192315] placeholder:text-[#595548]/65 focus:border-[#A44A28] focus:outline-none focus:ring-2 focus:ring-[#A44A28]/20"
            />
          </div>

          <Button
            type="submit"
            variant="indigo"
            size="sm"
            disabled={!aiInput.trim() || aiCartMutation.isPending}
            className="h-10 shrink-0 px-3"
          >
            {aiCartMutation.isPending ? (
              <Loader2 className="h-4 w-4 animate-spin" />
            ) : (
              "Agregar"
            )}
          </Button>
        </form>

        {/* Banner cuando la IA está hablando */}
        {isSpeaking && (
          <button
            type="button"
            onClick={stopSpeaking}
            className="flex w-full items-center justify-between rounded-xl border border-[#A44A28]/25 bg-white px-3 py-1.5 text-xs font-semibold text-[#7A3217] hover:bg-[#F8EFE9] cursor-pointer"
          >
            <span className="flex items-center gap-1.5">
              <Volume2 className="h-3.5 w-3.5 animate-pulse text-[#A44A28]" />
              Hablando... Toca para silenciar
            </span>
            <span className="text-[11px] font-bold underline">Silenciar</span>
          </button>
        )}

        {/* Banner de escucha activa con transcripción en vivo */}
        {isListening && (
          <div className="flex items-center justify-between gap-2 rounded-xl border border-[#9F2B2B]/25 bg-[#9F2B2B]/10 px-3 py-1.5 text-xs font-semibold text-[#9F2B2B]">
            <span className="flex min-w-0 items-center gap-2">
              <span className="relative flex h-2 w-2 shrink-0">
                <span className="absolute inline-flex h-full w-full animate-ping rounded-full bg-[#9F2B2B] opacity-75" />
                <span className="relative inline-flex h-2 w-2 rounded-full bg-[#9F2B2B]" />
              </span>
              <span className="truncate">
                {transcript ? `“${transcript}”` : "Escuchando pedido..."}
              </span>
            </span>
            <button
              type="button"
              onClick={stopListening}
              className="shrink-0 text-[11px] font-bold underline cursor-pointer"
            >
              Detener
            </button>
          </div>
        )}
      </div>

      {/* Mini tarjeta de confirmación pendiente por voz / manos libres */}
      {pendingAiProposal && (
        <div className="border-b border-[#A44A28]/25 bg-[#F8EFE9] p-3 space-y-2">
          <div className="flex items-center justify-between gap-2">
            <span className="text-xs font-bold text-[#192315]">
              ¿Agregar al carrito?
            </span>
            <Badge variant="indigo" className="font-mono-num">
              {formatMoney(pendingAiProposal.subtotalEstimado, moneda)}
            </Badge>
          </div>

          <ul className="space-y-1 text-xs text-[#4C493E]">
            {pendingAiProposal.items.map((it) => (
              <li
                key={`prop-${it.productoId}`}
                className="flex items-center justify-between gap-2"
              >
                <span className="truncate font-medium">
                  {formatNumber(it.cantidad, 2)} × {it.nombreProducto}
                  {it.excedeStock && (
                    <span className="ml-1.5 text-[10px] font-bold text-[#7A3217]">
                      (stock: {formatNumber(it.stockActual, 2)})
                    </span>
                  )}
                </span>
                <span className="font-mono-num shrink-0 font-semibold text-[#192315]">
                  {formatMoney(it.subtotal, moneda)}
                </span>
              </li>
            ))}
          </ul>

          {(pendingAiProposal.noEncontrados.length > 0 ||
            pendingAiProposal.advertencias.length > 0) && (
            <div className="flex items-start gap-1.5 rounded-lg border border-[#B7791F]/30 bg-[#D8B49A]/30 px-2.5 py-1.5 text-[11px] text-[#7A3217]">
              <AlertTriangle className="mt-0.5 h-3.5 w-3.5 shrink-0 text-[#B7791F]" />
              <span>
                {pendingAiProposal.advertencias[0] ||
                  `No encontrados: ${pendingAiProposal.noEncontrados.join(", ")}`}
              </span>
            </div>
          )}

          <div className="flex items-center gap-2 pt-0.5">
            <Button
              type="button"
              variant="emerald"
              size="sm"
              className="flex-1"
              onClick={() => confirmPendingProposal(false)}
            >
              <Check className="h-3.5 w-3.5" />
              Agregar al carrito
            </Button>
            <Button
              type="button"
              variant="ghost"
              size="sm"
              className="text-[#9F2B2B] hover:bg-[#9F2B2B]/10 hover:text-[#9F2B2B]"
              onClick={() => cancelPendingProposal(false)}
            >
              <X className="h-3.5 w-3.5" />
              Cancelar
            </Button>
          </div>
        </div>
      )}

      {/* Cabecera de lista del carrito */}
      <div className="flex items-center justify-between border-b border-[#192315]/12 px-3.5 py-2">
        <div className="flex items-center gap-2">
          <ShoppingCart className="h-4 w-4 text-[#595548]" />
          <span className="text-xs font-bold uppercase tracking-wide text-[#192315]">
            Ticket ({items.length} {items.length === 1 ? "ítem" : "ítems"} ·{" "}
            {formatNumber(totalUnidades, 2)} u.)
          </span>
        </div>

        <button
          type="button"
          disabled={items.length === 0}
          onClick={onClearCart}
          className="inline-flex items-center gap-1 rounded-lg px-2 py-1 text-[11px] font-semibold text-[#9F2B2B] transition-colors hover:bg-[#9F2B2B]/10 disabled:pointer-events-none disabled:opacity-40 cursor-pointer"
        >
          <Trash2 className="h-3.5 w-3.5" />
          Vaciar <span className="font-mono-num hidden sm:inline text-[#595548]">(ESC)</span>
        </button>
      </div>

      {/* Lista de ítems del carrito */}
      <div className="scrollbar-thin flex-1 overflow-y-auto px-3 py-2">
        {items.length === 0 ? (
          <div className="flex h-full min-h-35 flex-col items-center justify-center gap-2 text-center text-[#595548]">
            <ShoppingCart className="h-9 w-9 text-[#192315]/20" />
            <p className="text-xs font-semibold text-[#192315]">El carrito está vacío</p>
            <p className="max-w-57.5 text-[11px] text-[#595548]">
              Toca productos del catálogo o usa la barra de IA y voz arriba.
            </p>
          </div>
        ) : (
          <div className="divide-y divide-[#192315]/10">
            {items.map((item) => {
              const subtotal = round2(item.cantidad * item.precioUnitario);
              const atMaxStock =
                !item.esVentaLibre &&
                item.stockActual !== undefined &&
                item.cantidad >= item.stockActual;

              return (
                <div key={item.key} className="flex items-center justify-between gap-2 py-2.5">
                  <div className="min-w-0 flex-1">
                    <div className="flex items-center gap-1.5">
                      <p className="truncate text-xs font-bold text-[#192315]">
                        {item.nombreProducto}
                      </p>
                      {item.esVentaLibre && (
                        <Badge variant="amber" className="px-1.5 py-0 text-[10px]">
                          Libre
                        </Badge>
                      )}
                    </div>
                    <p className="font-mono-num text-[11px] text-[#595548]">
                      {formatMoney(item.precioUnitario, moneda)} c/u
                      {!item.esVentaLibre && item.stockActual !== undefined
                        ? ` · Stock ${formatNumber(item.stockActual, 2)}`
                        : ""}
                    </p>
                  </div>

                  {/* Controles táctiles [-][qty][+] */}
                  <div className="flex items-center gap-1">
                    <button
                      type="button"
                      aria-label={`Disminuir cantidad de ${item.nombreProducto}`}
                      onClick={() => onDecrementItem(item.key)}
                      className="flex h-8 w-8 items-center justify-center rounded-lg border border-[#192315]/16 bg-white text-[#192315] hover:bg-[#EAE3D2]/60 active:scale-95 cursor-pointer"
                    >
                      <Minus className="h-3.5 w-3.5" />
                    </button>

                    <span className="font-mono-num min-w-7 text-center text-xs font-extrabold text-[#192315]">
                      {formatNumber(item.cantidad, 2)}
                    </span>

                    <button
                      type="button"
                      aria-label={`Aumentar cantidad de ${item.nombreProducto}`}
                      disabled={atMaxStock}
                      onClick={() => onIncrementItem(item.key)}
                      className="flex h-8 w-8 items-center justify-center rounded-lg border border-[#192315]/16 bg-white text-[#192315] hover:bg-[#EAE3D2]/60 active:scale-95 disabled:cursor-not-allowed disabled:opacity-40 cursor-pointer"
                    >
                      <Plus className="h-3.5 w-3.5" />
                    </button>
                  </div>

                  <div className="flex items-center gap-1.5 pl-1">
                    <span className="font-mono-num min-w-16 text-right text-xs font-extrabold text-[#192315]">
                      {formatMoney(subtotal, moneda)}
                    </span>
                    <button
                      type="button"
                      aria-label={`Quitar ${item.nombreProducto}`}
                      onClick={() => onRemoveItem(item.key)}
                      className="rounded-lg p-1.5 text-[#595548]/70 hover:bg-[#9F2B2B]/10 hover:text-[#9F2B2B] cursor-pointer"
                    >
                      <Trash2 className="h-3.5 w-3.5" />
                    </button>
                  </div>
                </div>
              );
            })}
          </div>
        )}
      </div>

      {/* Panel de cobro y calculadora de vuelto */}
      <div className="border-t border-[#192315]/14 bg-[#EAE3D2]/50 p-3 space-y-2.5">
        {/* Cliente */}
        <div>
          <Select
            value={clienteId ? String(clienteId) : "general"}
            onValueChange={(val) => onClienteChange(val === "general" ? null : Number(val))}
            options={clienteOptions}
            className={cn(
              "min-h-9 text-xs",
              fiadoSinCliente && "border-[#B7791F] ring-2 ring-[#B7791F]/20",
            )}
          />
          {fiadoSinCliente && (
            <p className="mt-1 text-[11px] font-semibold text-[#7A3217]">
              Selecciona un cliente para registrar una venta fiada.
            </p>
          )}
        </div>

        {/* Método de Pago en 1 clic */}
        <div className="grid grid-cols-5 gap-1">
          {METODOS_POS.map((m) => {
            const Icon = m.icon;
            const active = metodoPago === m.value;
            return (
              <button
                key={m.value}
                type="button"
                onClick={() => onMetodoPagoChange(m.value)}
                className={cn(
                  "flex flex-col items-center justify-center gap-0.5 rounded-xl border py-1.5 text-[11px] font-bold transition-all cursor-pointer",
                  active
                    ? m.value === "FIADO"
                      ? "border-[#A44A28] bg-[#A44A28] text-[#FAF7EF] shadow-xs"
                      : "border-[#192315] bg-[#192315] text-[#F4EFE4] shadow-xs"
                    : "border-[#192315]/16 bg-white text-[#595548] hover:border-[#192315]/35 hover:text-[#192315]",
                )}
              >
                <Icon className="h-3.5 w-3.5" />
                <span>{m.label}</span>
              </button>
            );
          })}
        </div>

        {/* Calculadora de vuelto solo para Efectivo */}
        {metodoPago === "EFECTIVO" && (
          <div className="rounded-xl border border-[#192315]/16 bg-white p-2.5 space-y-2">
            <div className="flex items-center justify-between gap-2">
              <label
                htmlFor="pos-paga-con"
                className="text-xs font-bold text-[#192315] shrink-0"
              >
                Paga con:
              </label>
              <div className="relative w-36">
                <span className="font-mono-num pointer-events-none absolute left-2.5 top-1/2 -translate-y-1/2 text-xs font-bold text-[#595548]">
                  S/
                </span>
                <input
                  id="pos-paga-con"
                  type="number"
                  min="0"
                  step="0.10"
                  placeholder={total > 0 ? total.toFixed(2) : "0.00"}
                  value={montoRecibidoStr}
                  onChange={(e) => onMontoRecibidoChange(e.target.value)}
                  className="font-mono-num h-9 w-full rounded-lg border border-[#192315]/20 bg-[#FAF7EF] pl-7 pr-2.5 text-right text-sm font-extrabold text-[#192315] focus:border-[#A44A28] focus:bg-white focus:outline-none"
                />
              </div>
            </div>

            <div className="flex flex-wrap items-center gap-1">
              {BILLETES_RAPIDOS.map((billete) => (
                <button
                  key={billete}
                  type="button"
                  onClick={() => onMontoRecibidoChange(String(billete))}
                  className="font-mono-num flex-1 rounded-lg border border-[#192315]/16 bg-[#FAF7EF] px-2 py-1 text-[11px] font-bold text-[#192315] hover:bg-[#EAE3D2] cursor-pointer"
                >
                  S/ {billete}
                </button>
              ))}
              <button
                type="button"
                disabled={total <= 0}
                onClick={() => onMontoRecibidoChange(total.toFixed(2))}
                className="rounded-lg border border-[#263822]/25 bg-[#E9F0E6] px-2.5 py-1 text-[11px] font-bold text-[#263822] hover:bg-[#263822]/15 disabled:opacity-40 cursor-pointer"
              >
                Exacto
              </button>
            </div>

            <div className="flex items-center justify-between border-t border-[#192315]/12 pt-1.5">
              <span className="text-xs font-bold text-[#595548]">
                {faltaEfectivo ? "Falta:" : "Vuelto:"}
              </span>
              <span
                className={cn(
                  "font-mono-num text-lg font-extrabold",
                  faltaEfectivo ? "text-[#9F2B2B]" : "text-[#263822]",
                )}
              >
                {faltaEfectivo && montoRecibidoNum !== null
                  ? formatMoney(round2(total - montoRecibidoNum), moneda)
                  : formatMoney(vuelto, moneda)}
              </span>
            </div>
          </div>
        )}

        {/* Botón gigante COBRAR */}
        <Button
          type="button"
          variant="emerald"
          size="lg"
          loading={isCheckingOut}
          disabled={items.length === 0 || isCheckingOut}
          onClick={onCheckout}
          className="h-13 w-full justify-between rounded-xl px-4 text-base font-extrabold shadow-sm"
        >
          <span className="flex items-center gap-2">
            <CreditCard className="h-5 w-5" />
            <span>
              COBRAR <span className="font-mono-num">{formatMoney(total, moneda)}</span>
            </span>
          </span>
          <kbd className="font-mono-num rounded-md bg-[#192315]/40 px-2 py-0.5 text-xs font-bold text-[#F4EFE4]">
            F4
          </kbd>
        </Button>
      </div>
    </aside>
  );
}
