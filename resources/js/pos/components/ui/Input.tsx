import { forwardRef, type InputHTMLAttributes, type TextareaHTMLAttributes } from "react";
import { cn } from "@pos/components/ui/cn";

export interface InputProps extends InputHTMLAttributes<HTMLInputElement> {
  label?: string;
  error?: string;
  hint?: string;
}

export const Input = forwardRef<HTMLInputElement, InputProps>(
  ({ label, error, hint, className, id, ...props }, ref) => {
    const inputId = id ?? props.name;
    return (
      <div className="flex flex-col gap-1.5">
        {label && (
          <label htmlFor={inputId} className="text-xs font-semibold text-[#192315]">
            {label}
          </label>
        )}
        <input
          ref={ref}
          id={inputId}
          className={cn(
            "min-h-10 w-full rounded-xl border border-[#192315]/20 bg-white px-3.5 py-2 text-sm text-[#192315]",
            "placeholder:text-[#595548]/65 transition-colors",
            "focus:border-[#A44A28] focus:outline-none focus:ring-2 focus:ring-[#A44A28]/20",
            "disabled:bg-[#EAE3D2]/45 disabled:text-[#595548]/60",
            error && "border-[#9F2B2B] focus:border-[#9F2B2B] focus:ring-[#9F2B2B]/20",
            className,
          )}
          {...props}
        />
        {error ? (
          <p className="text-xs font-medium text-[#9F2B2B]">{error}</p>
        ) : hint ? (
          <p className="text-xs text-[#595548]">{hint}</p>
        ) : null}
      </div>
    );
  },
);
Input.displayName = "Input";

export interface TextareaProps extends TextareaHTMLAttributes<HTMLTextAreaElement> {
  label?: string;
  error?: string;
}

export const Textarea = forwardRef<HTMLTextAreaElement, TextareaProps>(
  ({ label, error, className, id, ...props }, ref) => {
    const areaId = id ?? props.name;
    return (
      <div className="flex flex-col gap-1.5">
        {label && (
          <label htmlFor={areaId} className="text-xs font-semibold text-[#192315]">
            {label}
          </label>
        )}
        <textarea
          ref={ref}
          id={areaId}
          className={cn(
            "w-full rounded-xl border border-[#192315]/20 bg-white px-3.5 py-2.5 text-sm text-[#192315]",
            "placeholder:text-[#595548]/65 transition-colors",
            "focus:border-[#A44A28] focus:outline-none focus:ring-2 focus:ring-[#A44A28]/20",
            error && "border-[#9F2B2B] focus:border-[#9F2B2B] focus:ring-[#9F2B2B]/20",
            className,
          )}
          {...props}
        />
        {error && <p className="text-xs font-medium text-[#9F2B2B]">{error}</p>}
      </div>
    );
  },
);
Textarea.displayName = "Textarea";
