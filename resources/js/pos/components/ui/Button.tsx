import type { ButtonHTMLAttributes } from "react";
import { Loader2 } from "lucide-react";
import { cn } from "@pos/components/ui/cn";

export type ButtonVariant =
  | "primary"
  | "indigo"
  | "emerald"
  | "danger"
  | "outline"
  | "ghost"
  | "secondary";

export type ButtonSize = "sm" | "md" | "lg" | "icon";

export interface ButtonProps extends ButtonHTMLAttributes<HTMLButtonElement> {
  variant?: ButtonVariant;
  size?: ButtonSize;
  loading?: boolean;
}

const VARIANT_CLASSES: Record<ButtonVariant, string> = {
  primary:
    "bg-[#192315] text-[#F4EFE4] hover:bg-[#263822] active:bg-[#141D11] shadow-xs focus-visible:ring-[#192315]",
  indigo:
    "bg-[#A44A28] text-[#FAF7EF] hover:bg-[#8B3C1E] active:bg-[#7A3217] shadow-xs focus-visible:ring-[#A44A28]",
  emerald:
    "bg-[#263822] text-[#F4EFE4] hover:bg-[#192315] active:bg-[#141D11] shadow-xs focus-visible:ring-[#263822]",
  danger:
    "bg-[#9F2B2B] text-white hover:bg-[#7F1D1D] active:bg-[#691717] shadow-xs focus-visible:ring-[#9F2B2B]",
  outline:
    "border border-[#192315]/22 bg-white text-[#192315] hover:border-[#192315] hover:bg-[#FAF7EF] focus-visible:ring-[#A44A28]/40",
  secondary:
    "border border-[#192315]/20 bg-[#FAF7EF] text-[#192315] hover:bg-white focus-visible:ring-[#192315]/40",
  ghost:
    "bg-transparent text-[#595548] hover:bg-[#EAE3D2]/60 hover:text-[#192315]",
};

const SIZE_CLASSES: Record<ButtonSize, string> = {
  sm: "min-h-9 px-3 text-xs rounded-lg gap-1.5",
  md: "min-h-10 px-4 py-2 text-sm rounded-xl gap-2",
  lg: "min-h-11 px-5 py-2.5 text-sm rounded-xl gap-2",
  icon: "h-10 w-10 rounded-xl p-0",
};

export function Button({
  variant = "primary",
  size = "md",
  loading = false,
  disabled,
  className,
  children,
  type = "button",
  ...props
}: ButtonProps) {
  return (
    <button
      type={type}
      disabled={disabled || loading}
      className={cn(
        "inline-flex items-center justify-center font-semibold transition-colors cursor-pointer select-none",
        "focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-offset-2 focus-visible:ring-offset-[#F4EFE4]",
        "disabled:opacity-50 disabled:pointer-events-none",
        VARIANT_CLASSES[variant],
        SIZE_CLASSES[size],
        className,
      )}
      {...props}
    >
      {loading && <Loader2 className="h-4 w-4 animate-spin shrink-0" />}
      {children}
    </button>
  );
}
