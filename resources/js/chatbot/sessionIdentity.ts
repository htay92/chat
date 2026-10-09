import type { ExchangeContext } from "./types";
export const UUID_V4 = /^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/;
export function secureUuid(): string {
    if (!globalThis.crypto?.getRandomValues) throw new Error("Secure randomness unavailable.");
    if (globalThis.crypto.randomUUID) return globalThis.crypto.randomUUID();
    const bytes = globalThis.crypto.getRandomValues(new Uint8Array(16));
    bytes[6] = (bytes[6]! & 15) | 64; bytes[8] = (bytes[8]! & 63) | 128;
    const hex = Array.from(bytes, (b) => b.toString(16).padStart(2, "0")).join("");
    return `${hex.slice(0, 8)}-${hex.slice(8, 12)}-${hex.slice(12, 16)}-${hex.slice(16, 20)}-${hex.slice(20)}`;
}
export function createSessionIdentity(options: { now?: () => number; uuid?: () => string; storage?: () => Storage } = {}) {
    const now = options.now ?? Date.now; const uuid = options.uuid ?? secureUuid;
    const storage = options.storage ?? (() => globalThis.sessionStorage);
    let memory: { id: string; issued: number } | undefined;
    let loaded = false;
    function sessionId(): string {
        if (!loaded) {
            loaded = true;
            try {
                const value = JSON.parse(storage().getItem("chatbot-session-v1") ?? "null");
                if (value && UUID_V4.test(value.id) && Number.isFinite(value.issued)) memory = value;
            } catch { /* Storage is optional; never persist messages. */ }
        }
        const time = now();
        if (!memory || time < memory.issued || time - memory.issued >= 86400000) {
            memory = { id: uuid(), issued: time };
            if (!UUID_V4.test(memory.id)) { memory = undefined; throw new Error("Invalid secure identifier."); }
            try { storage().setItem("chatbot-session-v1", JSON.stringify(memory)); } catch { /* Page memory fallback. */ }
        }
        return memory.id;
    }
    function exchange(): Readonly<ExchangeContext> { return Object.freeze({ sessionId: sessionId(), requestId: uuid() }); }
    return { sessionId, exchange };
}
