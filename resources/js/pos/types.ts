export type MetodoPago =
  | "EFECTIVO"
  | "YAPE"
  | "PLIN"
  | "TRANSFERENCIA"
  | "TARJETA"
  | "FIADO";

export interface Producto {
  id: number;
  codigoSku: string;
  nombre: string;
  categoria: string;
  precioCompra: number;
  precioVenta: number;
  stockActual: number;
  stockMinimo: number;
  unidadMedida: string;
  bajoStock: boolean;
  creadoEn?: string | null;
  actualizadoEn?: string | null;
}

export interface AlertaStock {
  productoId: number;
  nombre: string;
  stockActual: number;
  stockMinimo: number;
  unidadMedida: string;
}

export interface Cliente {
  id: number;
  nombre: string;
  telefono: string | null;
  documento: string | null;
  notas: string | null;
  saldoPendiente: number;
  creadoEn?: string | null;
}

export interface PosCatalogoData {
  productos: Producto[];
  categorias: string[];
}

export interface PosAiCartItem {
  productoId: number;
  nombreProducto: string;
  categoria: string;
  codigoSku: string;
  precioUnitario: number;
  stockActual: number;
  stockMinimo: number;
  unidadMedida: string;
  cantidad: number;
  subtotal: number;
  nombreDetectado: string;
  excedeStock: boolean;
}

export interface PosAiCartResponse {
  items: PosAiCartItem[];
  noEncontrados: string[];
  advertencias: string[];
  subtotalEstimado: number;
  metodoPagoDetectado?: MetodoPago | null;
  mensaje: string;
  mensaje_voz: string;
  requiere_confirmacion: boolean;
  tokensUsados: number;
  tiempoRespuestaMs: number;
}

export interface PosCheckoutItemPayload {
  productoId?: number;
  nombreProducto?: string;
  cantidad: number;
  precioUnitario?: number;
}

export interface PosCheckoutPayload {
  items: PosCheckoutItemPayload[];
  metodoPago: MetodoPago;
  clienteId?: number | null;
  clienteNombre?: string | null;
  montoRecibido?: number;
  origen?: "MANUAL" | "VOZ" | "IA";
}

export interface PosCheckoutSaleItem {
  id: number;
  productoId: number | null;
  nombreProducto: string;
  cantidad: number;
  precioUnitario: number;
  subtotal: number;
}

export interface PosCheckoutVenta {
  id: number;
  referencia: string;
  clienteId: number | null;
  clienteNombre: string | null;
  total: number;
  metodoPago: MetodoPago;
  montoRecibido: number;
  vuelto: number;
  origen: string;
  fecha: string;
  items: PosCheckoutSaleItem[];
}

export interface PosCheckoutResponse {
  venta: PosCheckoutVenta;
  alertasStock: AlertaStock[];
  pago: {
    metodoPago: MetodoPago;
    total?: number;
    montoRecibido: number;
    vuelto: number;
    saldoPendienteCliente?: number | null;
  };
  mensaje_voz: string;
}

export interface PosCartItem {
  key: string;
  productoId?: number;
  nombreProducto: string;
  codigoSku?: string;
  categoria?: string;
  precioUnitario: number;
  cantidad: number;
  stockActual?: number;
  unidadMedida?: string;
  esVentaLibre: boolean;
}

export function round2(value: number): number {
  return Math.round((value + Number.EPSILON) * 100) / 100;
}
