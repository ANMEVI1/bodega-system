import type { ReactNode } from "react";
import * as DialogPrimitive from "@radix-ui/react-dialog";
import { X } from "lucide-react";
import { cn } from "@pos/components/ui/cn";

interface DialogProps {
  open: boolean;
  onOpenChange: (open: boolean) => void;
  title: string;
  description?: string;
  children: ReactNode;
  maxWidthClass?: string;
}

export function Dialog({
  open,
  onOpenChange,
  title,
  description,
  children,
  maxWidthClass = "max-w-lg",
}: DialogProps) {
  return (
    <DialogPrimitive.Root open={open} onOpenChange={onOpenChange}>
      <DialogPrimitive.Portal>
        <DialogPrimitive.Overlay className="fixed inset-0 z-50 bg-[#192315]/45 backdrop-blur-2xs data-[state=open]:animate-in data-[state=closed]:animate-out" />
        <DialogPrimitive.Content
          className={cn(
            "fixed left-1/2 top-1/2 z-50 w-[calc(100%-2rem)] -translate-x-1/2 -translate-y-1/2",
            "max-h-[90vh] overflow-y-auto rounded-2xl border border-[#192315]/16 bg-[#FAF7EF] p-6 shadow-xl focus:outline-none",
            maxWidthClass,
          )}
        >
          <div className="mb-4 flex items-start justify-between gap-4">
            <div>
              <DialogPrimitive.Title className="font-editorial text-xl font-semibold text-[#192315]">
                {title}
              </DialogPrimitive.Title>
              {description && (
                <DialogPrimitive.Description className="mt-1 text-xs text-[#595548]">
                  {description}
                </DialogPrimitive.Description>
              )}
            </div>
            <DialogPrimitive.Close
              aria-label="Cerrar"
              className="rounded-lg p-1.5 text-[#595548] transition-colors hover:bg-[#EAE3D2]/70 hover:text-[#192315] cursor-pointer"
            >
              <X className="h-5 w-5" />
            </DialogPrimitive.Close>
          </div>
          {children}
        </DialogPrimitive.Content>
      </DialogPrimitive.Portal>
    </DialogPrimitive.Root>
  );
}
