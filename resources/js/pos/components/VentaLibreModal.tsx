import { useEffect, useState, type FormEvent } from "react";
import { PlusCircle } from "lucide-react";
import { toast } from "sonner";
import { Button } from "@pos/components/ui/Button";
import { Dialog } from "@pos/components/ui/Dialog";
import { Input } from "@pos/components/ui/Input";
import { formatMoney } from "@pos/formatters";
import { round2 } from "@pos/types";

interface VentaLibreModalProps {
  open: boolean;
  onOpenChange: (open: boolean) => void;
  moneda: string;
  onAdd: (item: { nombreProducto: string; precioUnitario: number; cantidad: number }) => void;
}

export function VentaLibreModal({
  open,
  onOpenChange,
  moneda,
  onAdd,
}: VentaLibreModalProps) {
  const [descripcion, setDescripcion] = useState("");
  const [precio, setPrecio] = useState("");
  const [cantidad, setCantidad] = useState("1");

  useEffect(() => {
    if (open) {
      setDescripcion("");
      setPrecio("");
      setCantidad("1");
    }
  }, [open]);

  const precioNum = Number(precio);
  const cantidadNum = Number(cantidad);
  const subtotal =
    Number.isFinite(precioNum) && Number.isFinite(cantidadNum) && precioNum > 0 && cantidadNum > 0
      ? round2(precioNum * cantidadNum)
      : 0;

  const handleSubmit = (e: FormEvent) => {
    e.preventDefault();
    const nombreLimpio = descripcion.trim();
    if (!nombreLimpio) {
      toast.error("Ingresa una descripción para la venta libre");
      return;
    }
    if (!Number.isFinite(precioNum) || precioNum <= 0) {
      toast.error("Ingresa un precio unitario válido mayor a 0");
      return;
    }
    if (!Number.isFinite(cantidadNum) || cantidadNum <= 0) {
      toast.error("Ingresa una cantidad válida mayor a 0");
      return;
    }

    onAdd({
      nombreProducto: nombreLimpio,
      precioUnitario: round2(precioNum),
      cantidad: cantidadNum,
    });
    onOpenChange(false);
  };

  return (
    <Dialog
      open={open}
      onOpenChange={onOpenChange}
      title="Agregar Venta Libre / Varios"
      description="Ideal para productos sin código en catálogo, recargas o servicios rápidos."
      maxWidthClass="max-w-md"
    >
      <form onSubmit={handleSubmit} className="space-y-4">
        <Input
          label="Descripción del producto o servicio"
          name="venta-libre-descripcion"
          placeholder="Ej. Panetón suelto, Recarga Claro, Bolsa..."
          value={descripcion}
          onChange={(e) => setDescripcion(e.target.value)}
          maxLength={150}
          autoFocus
          required
        />

        <div className="grid grid-cols-2 gap-3">
          <Input
            label={`Precio unitario (${moneda})`}
            name="venta-libre-precio"
            type="number"
            min="0.01"
            step="0.01"
            placeholder="0.00"
            value={precio}
            onChange={(e) => setPrecio(e.target.value)}
            required
          />
          <Input
            label="Cantidad"
            name="venta-libre-cantidad"
            type="number"
            min="0.01"
            step="any"
            placeholder="1"
            value={cantidad}
            onChange={(e) => setCantidad(e.target.value)}
            required
          />
        </div>

        <div className="flex items-center justify-between rounded-xl border border-[#192315]/16 bg-[#EAE3D2]/45 px-3.5 py-2.5">
          <span className="text-xs font-semibold text-[#595548]">Subtotal a agregar</span>
          <span className="font-mono-num text-base font-extrabold text-[#192315]">
            {formatMoney(subtotal, moneda)}
          </span>
        </div>

        <div className="flex items-center justify-end gap-2 pt-1">
          <Button variant="outline" onClick={() => onOpenChange(false)}>
            Cancelar
          </Button>
          <Button type="submit" variant="emerald">
            <PlusCircle className="h-4 w-4" />
            Agregar al carrito
          </Button>
        </div>
      </form>
    </Dialog>
  );
}
