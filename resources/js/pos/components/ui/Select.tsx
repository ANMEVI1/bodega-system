import * as SelectPrimitive from "@radix-ui/react-select";
import { Check, ChevronDown } from "lucide-react";
import { cn } from "@pos/components/ui/cn";

export interface SelectOption {
  value: string;
  label: string;
}

interface SelectProps {
  label?: string;
  value: string;
  onValueChange: (value: string) => void;
  options: SelectOption[];
  placeholder?: string;
  disabled?: boolean;
  className?: string;
}

export function Select({
  label,
  value,
  onValueChange,
  options,
  placeholder = "Seleccionar...",
  disabled = false,
  className,
}: SelectProps) {
  return (
    <div className="flex flex-col gap-1.5">
      {label && <span className="text-xs font-semibold text-[#192315]">{label}</span>}
      <SelectPrimitive.Root value={value} onValueChange={onValueChange} disabled={disabled}>
        <SelectPrimitive.Trigger
          className={cn(
            "inline-flex min-h-10 w-full items-center justify-between gap-2 rounded-xl border border-[#192315]/20 bg-white px-3.5 py-2 text-sm text-[#192315]",
            "hover:border-[#192315]/35 focus:border-[#A44A28] focus:outline-none focus:ring-2 focus:ring-[#A44A28]/20",
            "disabled:bg-[#EAE3D2]/45 disabled:text-[#595548]/60 cursor-pointer",
            className,
          )}
        >
          <SelectPrimitive.Value placeholder={placeholder} />
          <SelectPrimitive.Icon>
            <ChevronDown className="h-4 w-4 text-[#595548] shrink-0" />
          </SelectPrimitive.Icon>
        </SelectPrimitive.Trigger>
        <SelectPrimitive.Portal>
          <SelectPrimitive.Content
            position="popper"
            sideOffset={6}
            className="z-50 max-h-64 min-w-(--radix-select-trigger-width) overflow-hidden rounded-xl border border-[#192315]/16 bg-[#FAF7EF] p-1 shadow-lg"
          >
            <SelectPrimitive.Viewport className="p-0.5">
              {options.map((opt) => (
                <SelectPrimitive.Item
                  key={opt.value}
                  value={opt.value}
                  className={cn(
                    "relative flex cursor-pointer select-none items-center rounded-lg py-2 pl-8 pr-3 text-sm text-[#192315] outline-none",
                    "data-highlighted:bg-[#A44A28]/10 data-highlighted:text-[#7A3217]",
                    "data-[state=checked]:font-semibold data-[state=checked]:text-[#192315]",
                  )}
                >
                  <span className="absolute left-2.5 flex h-3.5 w-3.5 items-center justify-center">
                    <SelectPrimitive.ItemIndicator>
                      <Check className="h-3.5 w-3.5 text-[#A44A28]" />
                    </SelectPrimitive.ItemIndicator>
                  </span>
                  <SelectPrimitive.ItemText>{opt.label}</SelectPrimitive.ItemText>
                </SelectPrimitive.Item>
              ))}
            </SelectPrimitive.Viewport>
          </SelectPrimitive.Content>
        </SelectPrimitive.Portal>
      </SelectPrimitive.Root>
    </div>
  );
}
