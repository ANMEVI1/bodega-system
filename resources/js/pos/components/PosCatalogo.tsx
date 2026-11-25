import { useMemo, useState, type RefObject } from "react";
import { AlertCircle, Package, Plus, RefreshCw, Search, X } from "lucide-react";
import { cn } from "@pos/components/ui/cn";
import { Button } from "@pos/components/ui/Button";
import { formatMoney, formatNumber } from "@pos/formatters";
import type { Producto } from "@pos/types";

interface PosCatalogoProps {
  productos: Producto[];
  categorias: string[];
  isLoading: boolean;
  isError: boolean;
  onRetry: () => void;
  moneda: string;
  cartQuantities: Record<number, number>;
  recentlyAddedId: number | null;
  searchInputRef: RefObject<HTMLInputElement | null>;
  onAddProduct: (producto: Producto) => void;
  onOpenVentaLibre: () => void;
}

function normalizeText(value: string): string {
  return value
    .toLowerCase()
    .normalize("NFD")
    .replace(/[\u0300-\u036f]/g, "");
}

export function PosCatalogo({
  productos,
  categorias,
  isLoading,
  isError,
  onRetry,
  moneda,
  cartQuantities,
  recentlyAddedId,
  searchInputRef,
  onAddProduct,
  onOpenVentaLibre,
}: PosCatalogoProps) {
  const [busqueda, setBusqueda] = useState("");
  const [categoriaActiva, setCategoriaActiva] = useState<string>("TODOS");

  const categoriasDisponibles = useMemo(() => {
    const set = new Set<string>(categorias);
    for (const p of productos) {
      if (p.categoria) set.add(p.categoria);
    }
    return Array.from(set).sort((a, b) => a.localeCompare(b, "es"));
  }, [categorias, productos]);

  const productosFiltrados = useMemo(() => {
    const term = normalizeText(busqueda.trim());
    return productos.filter((p) => {
      if (categoriaActiva !== "TODOS" && p.categoria !== categoriaActiva) {
        return false;
      }
      if (!term) return true;
      const nombreNorm = normalizeText(p.nombre);
      const skuNorm = normalizeText(p.codigoSku ?? "");
      const catNorm = normalizeText(p.categoria);
      return nombreNorm.includes(term) || skuNorm.includes(term) || catNorm.includes(term);
    });
  }, [productos, categoriaActiva, busqueda]);

  return (
    <section className="flex h-full flex-col overflow-hidden rounded-2xl border border-[#192315]/16 bg-[#FAF7EF] shadow-xs">
      {/* Cabecera de búsqueda y acción Venta Libre */}
      <div className="border-b border-[#192315]/14 bg-[#EAE3D2]/45 p-3 space-y-2.5">
        <div className="flex flex-wrap items-center gap-2">
          <div className="relative min-w-52.5 flex-1">
            <Search className="pointer-events-none absolute left-3.5 top-1/2 h-4.5 w-4.5 -translate-y-1/2 text-[#595548]" />
            <input
              ref={searchInputRef}
              id="pos-search-input"
              aria-label="Buscar producto por nombre o SKU"
              type="text"
              value={busqueda}
              onChange={(e) => setBusqueda(e.target.value)}
              placeholder="Buscar producto por nombre o código SKU..."
              className="min-h-11 w-full rounded-xl border border-[#192315]/20 bg-white pl-10 pr-20 text-sm font-medium text-[#192315] placeholder:text-[#595548]/65 focus:border-[#A44A28] focus:outline-none focus:ring-2 focus:ring-[#A44A28]/20"
            />
            <div className="absolute right-2.5 top-1/2 flex -translate-y-1/2 items-center gap-1.5">
              {busqueda && (
                <button
                  type="button"
                  onClick={() => {
                    setBusqueda("");
                    searchInputRef.current?.focus();
                  }}
                  aria-label="Limpiar búsqueda"
                  className="rounded-md p-1 text-[#595548] hover:bg-[#EAE3D2]/70 hover:text-[#192315] cursor-pointer"
                >
                  <X className="h-3.5 w-3.5" />
                </button>
              )}
              <kbd className="font-mono-num hidden sm:inline-flex items-center rounded-md border border-[#192315]/16 bg-[#EAE3D2]/70 px-1.5 py-0.5 text-[10px] font-bold text-[#595548]">
                F2
              </kbd>
            </div>
          </div>

          <Button
            type="button"
            variant="primary"
            size="lg"
            onClick={onOpenVentaLibre}
            className="min-h-11 shrink-0"
          >
            <Plus className="h-4 w-4" />
            <span>Venta Libre / Varios</span>
          </Button>
        </div>

        {/* Pills horizontales de categorías */}
        <div className="scrollbar-thin flex items-center gap-1.5 overflow-x-auto pb-0.5">
          <button
            type="button"
            onClick={() => setCategoriaActiva("TODOS")}
            className={cn(
              "shrink-0 rounded-full px-3.5 py-1.5 text-xs font-bold transition-colors cursor-pointer",
              categoriaActiva === "TODOS"
                ? "bg-[#192315] text-[#F4EFE4] shadow-xs"
                : "border border-[#192315]/16 bg-white text-[#595548] hover:border-[#192315]/30 hover:text-[#192315]",
            )}
          >
            Todos ({productos.length})
          </button>

          {categoriasDisponibles.map((cat) => (
            <button
              key={cat}
              type="button"
              onClick={() => setCategoriaActiva(cat)}
              className={cn(
                "shrink-0 rounded-full px-3.5 py-1.5 text-xs font-bold transition-colors cursor-pointer",
                categoriaActiva === cat
                  ? "bg-[#A44A28] text-[#FAF7EF] shadow-xs"
                  : "border border-[#192315]/16 bg-white text-[#595548] hover:border-[#192315]/30 hover:text-[#192315]",
              )}
            >
              {cat}
            </button>
          ))}
        </div>
      </div>

      {/* Grid táctil de productos */}
      <div className="scrollbar-thin flex-1 overflow-y-auto p-3">
        {isLoading ? (
          <div className="grid grid-cols-2 gap-2.5 sm:grid-cols-3 xl:grid-cols-4">
            {Array.from({ length: 12 }).map((_, idx) => (
              <div
                key={idx}
                className="h-32 animate-pulse rounded-2xl border border-[#192315]/14 bg-[#EAE3D2]/60"
              />
            ))}
          </div>
        ) : isError ? (
          <div className="flex h-full min-h-60 flex-col items-center justify-center gap-3 text-center">
            <AlertCircle className="h-9 w-9 text-[#9F2B2B]" />
            <div>
              <p className="text-sm font-bold text-[#192315]">
                No pudimos cargar el catálogo de caja
              </p>
              <p className="text-xs text-[#595548]">
                Verifica tu conexión e intenta nuevamente.
              </p>
            </div>
            <Button variant="outline" size="sm" onClick={onRetry}>
              <RefreshCw className="h-3.5 w-3.5" />
              Reintentar
            </Button>
          </div>
        ) : productosFiltrados.length === 0 ? (
          <div className="flex h-full min-h-60 flex-col items-center justify-center gap-2 text-center">
            <Package className="h-10 w-10 text-[#595548]/45" />
            <p className="text-sm font-bold text-[#192315]">
              No se encontraron productos
            </p>
            <p className="max-w-xs text-xs text-[#595548]">
              {busqueda
                ? `No hay coincidencias para "${busqueda}". Puedes usar "+ Venta Libre / Varios".`
                : "Aún no tienes productos en esta categoría."}
            </p>
          </div>
        ) : (
          <div className="grid grid-cols-2 gap-2.5 sm:grid-cols-3 xl:grid-cols-4">
            {productosFiltrados.map((producto) => {
              const agotado = producto.stockActual <= 0;
              const bajoStock = !agotado && producto.stockActual <= producto.stockMinimo;
              const qtyEnCarrito = cartQuantities[producto.id] ?? 0;
              const justAdded = recentlyAddedId === producto.id;

              return (
                <button
                  key={producto.id}
                  type="button"
                  disabled={agotado}
                  onClick={() => onAddProduct(producto)}
                  className={cn(
                    "group relative flex min-h-31.5 flex-col justify-between rounded-2xl border p-3 text-left transition-all select-none",
                    agotado
                      ? "cursor-not-allowed border-[#9F2B2B]/25 bg-[#9F2B2B]/10 opacity-65"
                      : "cursor-pointer border-[#192315]/14 bg-white hover:border-[#A44A28]/60 hover:shadow-sm active:scale-98",
                    justAdded && "border-[#263822] bg-[#E9F0E6] ring-2 ring-[#263822]/35",
                  )}
                >
                  {/* Fila superior: Categoría + Semáforo Stock */}
                  <div className="flex items-start justify-between gap-1.5">
                    <span className="max-w-[55%] truncate rounded-md bg-[#EAE3D2]/75 px-1.5 py-0.5 text-[10px] font-bold uppercase tracking-wide text-[#4C493E]">
                      {producto.categoria || "General"}
                    </span>

                    {agotado ? (
                      <span className="inline-flex items-center gap-1 rounded-full bg-[#9F2B2B]/12 px-2 py-0.5 text-[10px] font-bold text-[#9F2B2B]">
                        <span className="h-1.5 w-1.5 rounded-full bg-[#9F2B2B]" />
                        Agotado
                      </span>
                    ) : bajoStock ? (
                      <span className="font-mono-num inline-flex items-center gap-1 rounded-full bg-[#D8B49A]/35 px-2 py-0.5 text-[10px] font-bold text-[#7A3217]">
                        <span className="h-1.5 w-1.5 rounded-full bg-[#A44A28]" />
                        {formatNumber(producto.stockActual, 2)}
                      </span>
                    ) : (
                      <span className="font-mono-num inline-flex items-center gap-1 rounded-full bg-[#E9F0E6] px-2 py-0.5 text-[10px] font-bold text-[#263822]">
                        <span className="h-1.5 w-1.5 rounded-full bg-[#3F5D39]" />
                        {formatNumber(producto.stockActual, 2)}
                      </span>
                    )}
                  </div>

                  {/* Nombre y SKU */}
                  <div className="my-2">
                    <p className="line-clamp-2 text-sm font-bold leading-snug text-[#192315] group-hover:text-[#7A3217]">
                      {producto.nombre}
                    </p>
                    <p className="font-mono-num mt-0.5 truncate text-[10px] text-[#595548]">
                      {producto.codigoSku ? `${producto.codigoSku} · ` : ""}
                      {producto.unidadMedida}
                    </p>
                  </div>

                  {/* Precio y badge de cantidad en carrito */}
                  <div className="flex items-center justify-between gap-1 border-t border-[#192315]/12 pt-2">
                    <span className="font-mono-num text-base font-bold text-[#192315]">
                      {formatMoney(producto.precioVenta, moneda)}
                    </span>

                    {qtyEnCarrito > 0 && (
                      <span className="font-mono-num inline-flex h-6 min-w-6 items-center justify-center rounded-full bg-[#A44A28] px-2 text-xs font-extrabold text-[#FAF7EF] shadow-xs">
                        ×{formatNumber(qtyEnCarrito, 2)}
                      </span>
                    )}
                  </div>
                </button>
              );
            })}
          </div>
        )}
      </div>
    </section>
  );
}
