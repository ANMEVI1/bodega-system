import { useCallback, useEffect, useRef, useState } from "react";

interface SpeechRecognitionAlternativeLike {
  transcript: string;
  confidence: number;
}

interface SpeechRecognitionResultLike {
  isFinal: boolean;
  length: number;
  [index: number]: SpeechRecognitionAlternativeLike;
}

interface SpeechRecognitionResultListLike {
  length: number;
  [index: number]: SpeechRecognitionResultLike;
}

interface SpeechRecognitionEventLike {
  resultIndex: number;
  results: SpeechRecognitionResultListLike;
}

interface SpeechRecognitionErrorEventLike {
  error: string;
}

interface SpeechRecognitionInstance {
  lang: string;
  continuous: boolean;
  interimResults: boolean;
  maxAlternatives: number;
  onstart: (() => void) | null;
  onresult: ((event: SpeechRecognitionEventLike) => void) | null;
  onerror: ((event: SpeechRecognitionErrorEventLike) => void) | null;
  onend: (() => void) | null;
  start: () => void;
  stop: () => void;
  abort: () => void;
}

type SpeechRecognitionConstructor = new () => SpeechRecognitionInstance;

interface WindowWithSpeech extends Window {
  SpeechRecognition?: SpeechRecognitionConstructor;
  webkitSpeechRecognition?: SpeechRecognitionConstructor;
}

function getSpeechRecognitionCtor(): SpeechRecognitionConstructor | null {
  if (typeof window === "undefined") return null;
  const win = window as WindowWithSpeech;
  return win.SpeechRecognition ?? win.webkitSpeechRecognition ?? null;
}

function hasSpeechSynthesis(): boolean {
  return typeof window !== "undefined" && "speechSynthesis" in window;
}

function normalizarTextoVoz(texto: string): string {
  return texto
    .toLowerCase()
    .normalize("NFD")
    .replace(/[\u0300-\u036f]/g, "")
    .replace(/[¿?¡!.,;:()[\]"']/g, " ")
    .replace(/\s+/g, " ")
    .trim();
}

const FRASES_CONFIRMACION = [
  "si",
  "sip",
  "claro",
  "confirmo",
  "confirmar",
  "confirmalo",
  "dale",
  "ok",
  "okay",
  "okey",
  "listo",
  "perfecto",
  "de acuerdo",
  "por supuesto",
  "adelante",
  "hazlo",
  "agrega",
  "agregalo",
  "agregalos",
  "agregar",
  "agregarlo",
  "agregarlos",
  "agreguelos",
  "anade",
  "anadelo",
  "anadelos",
  "guarda",
  "guardalo",
  "guardar",
  "registra",
  "registralo",
  "registrar",
  "procede",
  "acepto",
  "aceptar",
  "esta bien",
  "bueno",
];

const FRASES_CANCELACION = [
  "no",
  "nop",
  "nope",
  "cancela",
  "cancelar",
  "cancelalo",
  "descarta",
  "descartar",
  "descartalo",
  "olvida",
  "olvidalo",
  "mejor no",
  "detente",
  "para",
  "negativo",
  "borra",
  "borrar",
  "no agregues",
  "no lo agregues",
  "no los agregues",
  "no guardes",
];

/**
 * Determina si una transcripción de voz expresa confirmación afirmativa.
 */
export function esConfirmacionVoz(texto: string): boolean {
  const norm = normalizarTextoVoz(texto);
  if (!norm) return false;
  if (norm === "no" || norm.startsWith("no ") || norm.includes("mejor no")) {
    return false;
  }
  return FRASES_CONFIRMACION.some(
    (frase) => norm === frase || norm.startsWith(`${frase} `) || norm.endsWith(` ${frase}`),
  );
}

/**
 * Determina si una transcripción de voz expresa cancelación o rechazo.
 */
export function esCancelacionVoz(texto: string): boolean {
  const norm = normalizarTextoVoz(texto);
  if (!norm) return false;
  return FRASES_CANCELACION.some(
    (frase) => norm === frase || norm.startsWith(`${frase} `) || norm.endsWith(` ${frase}`),
  );
}

/**
 * Limpia precios en soles, markdown, URLs y símbolos para que la lectura TTS suene natural en español.
 */
export function limpiarTextoParaVoz(texto: string): string {
  if (!texto) return "";

  let limpio = texto;

  limpio = limpio.replace(/https?:\/\/\S+/gi, "");

  limpio = limpio.replace(/S\/\s*(\d+(?:,\d{3})*(?:\.\d{1,2})?)/gi, (_match, rawNum: string) => {
    const normalized = rawNum.replace(/,/g, "");
    const [intPartStr, decPartStr] = normalized.split(".");
    const entero = Number.parseInt(intPartStr, 10);
    if (Number.isNaN(entero)) return rawNum;

    const unidad = entero === 1 ? "sol" : "soles";
    if (!decPartStr) {
      return `${entero} ${unidad}`;
    }

    const paddedDec = decPartStr.padEnd(2, "0").slice(0, 2);
    const centimos = Number.parseInt(paddedDec, 10);
    if (!centimos) {
      return `${entero} ${unidad}`;
    }

    return `${entero} ${unidad} con ${paddedDec}`;
  });

  limpio = limpio
    .replace(/```[\s\S]*?```/g, " ")
    .replace(/`([^`]+)`/g, "$1")
    .replace(/[*_~#>|]/g, " ")
    .replace(/\[([^\]]+)\]\([^)]+\)/g, "$1")
    .replace(/^\s*[-•+]\s+/gm, "")
    .replace(/[✓✔✗✘★☆►▪▫◆◇■□●○]/g, " ");

  limpio = limpio
    .replace(/\r?\n+/g, ". ")
    .replace(/\s*\.\s*\./g, ".")
    .replace(/\s+/g, " ")
    .trim();

  return limpio;
}

function seleccionarMejorVozEspanol(voices: SpeechSynthesisVoice[]): SpeechSynthesisVoice | null {
  if (voices.length === 0) return null;

  const esVoices = voices.filter((v) => v.lang.toLowerCase().startsWith("es"));
  if (esVoices.length === 0) return null;

  const vozPeru = esVoices.find((v) => v.lang.toLowerCase().startsWith("es-pe"));
  if (vozPeru) return vozPeru;

  const vozGoogle = esVoices.find((v) => v.name.toLowerCase().includes("google"));
  if (vozGoogle) return vozGoogle;

  const vozMicrosoft = esVoices.find((v) =>
    /(helena|sabina|camila|raul)/i.test(v.name),
  );
  if (vozMicrosoft) return vozMicrosoft;

  return esVoices[0];
}

export interface SpeakOptions {
  autoListenAfter?: boolean;
  forceAutoListen?: boolean;
}

export interface UseVoiceAssistantOptions {
  lang?: string;
  initialHandsFreeMode?: boolean;
  onTranscriptReady?: (textoFinal: string) => void;
}

export function useVoiceAssistant(options: UseVoiceAssistantOptions = {}) {
  const {
    lang = "es-PE",
    initialHandsFreeMode = false,
    onTranscriptReady,
  } = options;

  const [isSupported] = useState<boolean>(() => getSpeechRecognitionCtor() !== null);
  const [ttsSupported] = useState<boolean>(() => hasSpeechSynthesis());
  const [isListening, setIsListening] = useState(false);
  const [isSpeaking, setIsSpeaking] = useState(false);
  const [isHandsFreeMode, setHandsFreeModeState] = useState(initialHandsFreeMode);
  const [transcript, setTranscript] = useState("");
  const [error, setError] = useState<string | null>(null);

  const recognitionRef = useRef<SpeechRecognitionInstance | null>(null);
  const activeLangRef = useRef<string>(lang);
  const fallbackAttemptedRef = useRef(false);
  const abortedManuallyRef = useRef(false);
  const isSpeakingRef = useRef(false);
  const isHandsFreeModeRef = useRef(initialHandsFreeMode);
  const autoListenTimerRef = useRef<number | null>(null);
  const onTranscriptReadyRef = useRef(onTranscriptReady);
  const voicesRef = useRef<SpeechSynthesisVoice[]>([]);
  const activeUtteranceRef = useRef<SpeechSynthesisUtterance | null>(null);

  useEffect(() => {
    onTranscriptReadyRef.current = onTranscriptReady;
  }, [onTranscriptReady]);

  useEffect(() => {
    activeLangRef.current = lang;
    fallbackAttemptedRef.current = false;
  }, [lang]);

  useEffect(() => {
    isHandsFreeModeRef.current = isHandsFreeMode;
  }, [isHandsFreeMode]);

  useEffect(() => {
    if (!hasSpeechSynthesis()) return;

    const loadVoices = () => {
      voicesRef.current = window.speechSynthesis.getVoices();
    };

    loadVoices();
    window.speechSynthesis.addEventListener?.("voiceschanged", loadVoices);
    return () => {
      window.speechSynthesis.removeEventListener?.("voiceschanged", loadVoices);
    };
  }, []);

  const clearAutoListenTimer = useCallback(() => {
    if (autoListenTimerRef.current !== null) {
      window.clearTimeout(autoListenTimerRef.current);
      autoListenTimerRef.current = null;
    }
  }, []);

  const stopSpeaking = useCallback(() => {
    clearAutoListenTimer();
    activeUtteranceRef.current = null;
    if (hasSpeechSynthesis()) {
      try {
        window.speechSynthesis.cancel();
      } catch {
        // Ignorar errores al cancelar síntesis
      }
    }
    isSpeakingRef.current = false;
    setIsSpeaking(false);
  }, [clearAutoListenTimer]);

  const stopListening = useCallback(() => {
    clearAutoListenTimer();
    if (recognitionRef.current) {
      abortedManuallyRef.current = true;
      try {
        recognitionRef.current.stop();
      } catch {
        // Ignorar si ya estaba detenido
      }
    }
    setIsListening(false);
  }, [clearAutoListenTimer]);

  const startListeningInternal = useCallback((targetLang: string) => {
    const Ctor = getSpeechRecognitionCtor();
    if (!Ctor) {
      setError("Tu navegador no soporta dictado por voz. Prueba en Chrome o Edge.");
      return;
    }

    if (isSpeakingRef.current) {
      stopSpeaking();
    }
    clearAutoListenTimer();

    if (recognitionRef.current) {
      abortedManuallyRef.current = true;
      try {
        recognitionRef.current.abort();
      } catch {
        // Ignorar
      }
      recognitionRef.current = null;
    }

    setError(null);
    setTranscript("");
    abortedManuallyRef.current = false;

    const recognition = new Ctor();
    recognition.lang = targetLang;
    recognition.continuous = false;
    recognition.interimResults = true;
    recognition.maxAlternatives = 1;

    let latestTranscript = "";
    let finalTranscript = "";
    let shouldRetryFallback = false;

    recognition.onstart = () => {
      setIsListening(true);
    };

    recognition.onresult = (event) => {
      if (isSpeakingRef.current) return;

      let combined = "";
      let finalsOnly = "";

      for (let i = 0; i < event.results.length; i += 1) {
        const res = event.results[i];
        const piece = res[0]?.transcript ?? "";
        combined += piece;
        if (res.isFinal) {
          finalsOnly += piece;
        }
      }

      latestTranscript = combined.trim();
      if (finalsOnly.trim()) {
        finalTranscript = finalsOnly.trim();
      }
      setTranscript(latestTranscript);
    };

    recognition.onerror = (event) => {
      if (
        event.error === "language-not-supported" &&
        targetLang !== "es-ES" &&
        !fallbackAttemptedRef.current
      ) {
        fallbackAttemptedRef.current = true;
        activeLangRef.current = "es-ES";
        shouldRetryFallback = true;
        return;
      }

      if (event.error === "not-allowed" || event.error === "service-not-allowed") {
        setError("Permiso de micrófono denegado. Actívalo en tu navegador para usar voz.");
      } else if (event.error === "no-speech") {
        setError("No se detectó voz. Habla cerca del micrófono e intenta de nuevo.");
      } else if (event.error !== "aborted") {
        setError("No pudimos capturar el audio. Intenta escribiendo o vuelve a hablar.");
      }
      setIsListening(false);
    };

    recognition.onend = () => {
      setIsListening(false);
      recognitionRef.current = null;

      if (shouldRetryFallback) {
        startListeningInternal("es-ES");
        return;
      }

      const textoFinal = (finalTranscript || latestTranscript).trim();
      if (!abortedManuallyRef.current && !isSpeakingRef.current && textoFinal) {
        onTranscriptReadyRef.current?.(textoFinal);
      }
    };

    recognitionRef.current = recognition;
    try {
      recognition.start();
    } catch {
      recognitionRef.current = null;
      setError("No se pudo iniciar el micrófono.");
      setIsListening(false);
    }
  }, [clearAutoListenTimer, stopSpeaking]);

  const startListening = useCallback(() => {
    startListeningInternal(activeLangRef.current);
  }, [startListeningInternal]);

  const speak = useCallback(
    (texto: string, speakOptions: SpeakOptions = {}) => {
      const { autoListenAfter = false, forceAutoListen = false } = speakOptions;
      if (!hasSpeechSynthesis()) return;

      const textoLimpio = limpiarTextoParaVoz(texto);
      if (!textoLimpio) return;

      if (recognitionRef.current) {
        abortedManuallyRef.current = true;
        try {
          recognitionRef.current.abort();
        } catch {
          // Ignorar
        }
        recognitionRef.current = null;
        setIsListening(false);
      }

      clearAutoListenTimer();
      try {
        window.speechSynthesis.cancel();
      } catch {
        // Ignorar
      }

      const utterance = new SpeechSynthesisUtterance(textoLimpio);
      const voices =
        voicesRef.current.length > 0
          ? voicesRef.current
          : window.speechSynthesis.getVoices();
      const selectedVoice = seleccionarMejorVozEspanol(voices);

      if (selectedVoice) {
        utterance.voice = selectedVoice;
        utterance.lang = selectedVoice.lang;
      } else {
        utterance.lang = activeLangRef.current || "es-PE";
      }

      utterance.rate = 1.02;
      utterance.pitch = 1.0;
      activeUtteranceRef.current = utterance;

      utterance.onstart = () => {
        if (activeUtteranceRef.current !== utterance) return;
        isSpeakingRef.current = true;
        setIsSpeaking(true);
      };

      utterance.onend = () => {
        if (activeUtteranceRef.current !== utterance) return;
        activeUtteranceRef.current = null;
        isSpeakingRef.current = false;
        setIsSpeaking(false);

        const shouldAutoListen =
          autoListenAfter && (isHandsFreeModeRef.current || forceAutoListen);
        if (shouldAutoListen) {
          clearAutoListenTimer();
          autoListenTimerRef.current = window.setTimeout(() => {
            autoListenTimerRef.current = null;
            startListeningInternal(activeLangRef.current);
          }, 250);
        }
      };

      utterance.onerror = () => {
        if (activeUtteranceRef.current !== utterance) return;
        activeUtteranceRef.current = null;
        isSpeakingRef.current = false;
        setIsSpeaking(false);
      };

      isSpeakingRef.current = true;
      setIsSpeaking(true);
      window.speechSynthesis.speak(utterance);
    },
    [clearAutoListenTimer, startListeningInternal],
  );

  const setHandsFreeMode = useCallback(
    (enabled: boolean) => {
      isHandsFreeModeRef.current = enabled;
      setHandsFreeModeState(enabled);
      if (!enabled) {
        stopSpeaking();
      }
    },
    [stopSpeaking],
  );

  const toggleHandsFreeMode = useCallback(() => {
    setHandsFreeMode(!isHandsFreeModeRef.current);
  }, [setHandsFreeMode]);

  const clearError = useCallback(() => {
    setError(null);
  }, []);

  useEffect(() => {
    return () => {
      clearAutoListenTimer();
      if (recognitionRef.current) {
        abortedManuallyRef.current = true;
        try {
          recognitionRef.current.abort();
        } catch {
          // Ignorar
        }
        recognitionRef.current = null;
      }
      if (hasSpeechSynthesis()) {
        try {
          window.speechSynthesis.cancel();
        } catch {
          // Ignorar
        }
      }
      isSpeakingRef.current = false;
    };
  }, [clearAutoListenTimer]);

  return {
    isSupported,
    ttsSupported,
    isListening,
    isSpeaking,
    isHandsFreeMode,
    setHandsFreeMode,
    toggleHandsFreeMode,
    transcript,
    error,
    clearError,
    startListening,
    stopListening,
    speak,
    stopSpeaking,
  };
}
