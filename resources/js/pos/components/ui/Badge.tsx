import type { HTMLAttributes } from "react";
import { cn } from "@pos/components/ui/cn";

export type BadgeVariant =
  | "default"
  | "emerald"
  | "coral"
  | "rose"
  | "amber"
  | "indigo"
  | "slate"
  | "outline";

const BADGE_CLASSES: Record<BadgeVariant, string> = {
  default: "bg-[#EAE3D2] text-[#192315] border-[#192315]/14",
  slate: "bg-[#EAE3D2] text-[#192315] border-[#192315]/14",
  indigo: "bg-[#A44A28]/12 text-[#7A3217] border-[#A44A28]/25",
  emerald: "bg-[#263822]/12 text-[#263822] border-[#263822]/25",
  amber: "bg-[#D8B49A]/35 text-[#7A3217] border-[#B7791F]/30",
  rose: "bg-[#9F2B2B]/12 text-[#9F2B2B] border-[#9F2B2B]/25",
  coral: "bg-[#9F2B2B]/12 text-[#9F2B2B] border-[#9F2B2B]/25",
  outline: "bg-white text-[#595548] border-[#192315]/16",
};

export interface BadgeProps extends HTMLAttributes<HTMLSpanElement> {
  variant?: BadgeVariant;
}

export function Badge({ variant = "default", className, ...props }: BadgeProps) {
  return (
    <span
      className={cn(
        "inline-flex items-center gap-1 rounded-full border px-2.5 py-0.5 text-xs font-semibold whitespace-nowrap",
        BADGE_CLASSES[variant],
        className,
      )}
      {...props}
    />
  );
}
