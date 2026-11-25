import { useEffect } from "react";
import { AlertTriangle, CheckCircle2, PlusCircle, Printer } from "lucide-react";
import { Button } from "@pos/components/ui/Button";
import { Dialog } from "@pos/components/ui/Dialog";
import {
  formatDateTime,
  formatMoney,
  formatNumber,
  METODO_PAGO_LABELS,
} from "@pos/formatters";
import type { PosCheckoutResponse } from "@pos/types";

interface TicketModalProps {
  open: boolean;
  checkoutResult: PosCheckoutResponse | null;
  businessName: string;
  moneda: string;
  onNewSale: () => void;
}

export function TicketModal({
  open,
  checkoutResult,
  businessName,
  moneda,
  onNewSale,
}: TicketModalProps) {
  useEffect(() => {
    const cleanupPrintClass = () => {
      document.body.classList.remove("pos-printing");
    };
    window.addEventListener("afterprint", cleanupPrintClass);
    return () => {
      window.removeEventListener("afterprint", cleanupPrintClass);
      cleanupPrintClass();
    };
  }, []);

  if (!checkoutResult) return null;

  const { venta, pago, alertasStock } = checkoutResult;

  const handlePrint = () => {
    document.body.classList.add("pos-printing");
    window.print();
    window.setTimeout(() => {
      document.body.classList.remove("pos-printing");
    }, 800);
  };

  return (
    <Dialog
      open={open}
      onOpenChange={(isOpen) => {
        if (!isOpen) onNewSale();
      }}
      title="Comprobante de Venta"
      description="Venta registrada correctamente en el sistema."
      maxWidthClass="max-w-md"
    >
      <div className="space-y-4">
        {/* Banner de éxito (no se imprime) */}
        <div className="pos-no-print flex items-center gap-3 rounded-xl border border-[#263822]/25 bg-[#E9F0E6] px-3.5 py-2.5 text-[#263822]">
          <CheckCircle2 className="h-5 w-5 shrink-0 text-[#263822]" />
          <div className="min-w-0">
            <p className="text-sm font-bold">¡Cobro realizado con éxito!</p>
            <p className="text-xs text-[#263822]/85">
              Ticket #{venta.id} · {METODO_PAGO_LABELS[pago.metodoPago] ?? pago.metodoPago}
            </p>
          </div>
        </div>

        {/* Área imprimible estilo ticket térmico */}
        <div className="pos-ticket-print-area rounded-2xl border border-dashed border-[#192315]/25 bg-white p-4 text-xs text-[#192315]">
          <div className="border-b border-dashed border-[#192315]/25 pb-3 text-center">
            <p className="font-editorial text-lg font-semibold uppercase tracking-tight text-[#192315]">
              {businessName || "Bodega Los Olivos"}
            </p>
            <p className="font-mono-num mt-1 text-[11px] font-bold text-[#4C493E]">
              TICKET N.º {String(venta.id).padStart(6, "0")}
            </p>
            {venta.referencia && (
              <p className="font-mono-num text-[10px] text-[#595548]">{venta.referencia}</p>
            )}
            <p className="text-[11px] text-[#595548]">{formatDateTime(venta.fecha)}</p>
          </div>

          <div className="space-y-1 border-b border-dashed border-[#192315]/25 py-2.5 text-[11px]">
            <div className="flex justify-between gap-2">
              <span className="text-[#595548]">Cliente:</span>
              <span className="font-semibold text-[#192315] text-right">
                {venta.clienteNombre ?? "Cliente General / Mostrador"}
              </span>
            </div>
            <div className="flex justify-between gap-2">
              <span className="text-[#595548]">Método de pago:</span>
              <span className="font-semibold text-[#192315]">
                {METODO_PAGO_LABELS[pago.metodoPago] ?? pago.metodoPago}
              </span>
            </div>
          </div>

          <div className="border-b border-dashed border-[#192315]/25 py-2.5">
            <div className="mb-1.5 flex justify-between text-[10px] font-bold uppercase tracking-wider text-[#595548]">
              <span>Detalle</span>
              <span>Importe</span>
            </div>
            <div className="space-y-1.5">
              {venta.items.map((det) => (
                <div key={det.id} className="flex items-start justify-between gap-2">
                  <div className="min-w-0">
                    <p className="font-semibold text-[#192315]">{det.nombreProducto}</p>
                    <p className="font-mono-num text-[11px] text-[#595548]">
                      {formatNumber(det.cantidad, 3)} ×{" "}
                      {formatMoney(det.precioUnitario, moneda)}
                    </p>
                  </div>
                  <span className="font-mono-num shrink-0 font-bold text-[#192315]">
                    {formatMoney(det.subtotal, moneda)}
                  </span>
                </div>
              ))}
            </div>
          </div>

          <div className="space-y-1 pt-2.5">
            <div className="flex items-center justify-between text-sm font-extrabold text-[#192315]">
              <span>TOTAL</span>
              <span className="font-mono-num">{formatMoney(venta.total, moneda)}</span>
            </div>

            {pago.metodoPago === "EFECTIVO" && (
              <>
                <div className="flex items-center justify-between text-xs text-[#595548]">
                  <span>Recibido</span>
                  <span className="font-mono-num font-semibold text-[#192315]">
                    {formatMoney(pago.montoRecibido, moneda)}
                  </span>
                </div>
                <div className="flex items-center justify-between text-sm font-extrabold text-[#263822]">
                  <span>Vuelto</span>
                  <span className="font-mono-num">{formatMoney(pago.vuelto, moneda)}</span>
                </div>
              </>
            )}
          </div>

          <p className="mt-4 text-center text-[11px] font-medium text-[#595548]">
            ¡Gracias por su compra!
          </p>
        </div>

        {/* Alertas de stock bajo si existieran (no se imprimen) */}
        {alertasStock.length > 0 && (
          <div className="pos-no-print rounded-xl border border-[#B7791F]/30 bg-[#D8B49A]/30 p-3 text-xs text-[#7A3217]">
            <div className="flex items-center gap-1.5 font-bold">
              <AlertTriangle className="h-4 w-4 text-[#B7791F]" />
              <span>Alerta de stock bajo</span>
            </div>
            <ul className="mt-1 space-y-0.5 text-[11px]">
              {alertasStock.map((a) => (
                <li key={a.productoId} className="font-mono-num">
                  • {a.nombre}: quedan {formatNumber(a.stockActual, 2)} {a.unidadMedida}
                </li>
              ))}
            </ul>
          </div>
        )}

        {/* Botones de acción (no se imprimen) */}
        <div className="pos-no-print flex items-center gap-2 pt-1">
          <Button
            type="button"
            variant="outline"
            size="lg"
            onClick={handlePrint}
            className="flex-1"
          >
            <Printer className="h-4 w-4" />
            Imprimir Ticket
          </Button>
          <Button
            type="button"
            variant="emerald"
            size="lg"
            onClick={onNewSale}
            className="flex-1"
            autoFocus
          >
            <PlusCircle className="h-4 w-4" />
            Nueva Venta
          </Button>
        </div>
      </div>
    </Dialog>
  );
}
