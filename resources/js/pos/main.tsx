import { StrictMode } from "react";
import { createRoot } from "react-dom/client";
import { PosApp } from "@pos/PosApp";

const rootEl = document.getElementById("pos-root");

if (rootEl) {
  const businessName = rootEl.dataset.businessName || "Bodega Los Olivos";
  const currency = rootEl.dataset.currency || "PEN";
  const homeUrl = rootEl.dataset.homeUrl || "/";

  createRoot(rootEl).render(
    <StrictMode>
      <PosApp
        businessName={businessName}
        currency={currency}
        homeUrl={homeUrl}
      />
    </StrictMode>,
  );
}
