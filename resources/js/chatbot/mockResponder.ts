import type { ChatResponder } from "./types";

export const mockResponder: ChatResponder = (message, signal) =>
    new Promise((resolve, reject) => {
        if (signal.aborted) {
            reject(new DOMException("Aborted", "AbortError"));
            return;
        }
        const abort = () => {
            clearTimeout(timer);
            signal.removeEventListener("abort", abort);
            reject(new DOMException("Aborted", "AbortError"));
        };
        const timer = setTimeout(() => {
            signal.removeEventListener("abort", abort);
            resolve(`Mock reply: You said: ${message}`);
        }, 500);
        signal.addEventListener("abort", abort, { once: true });
    });
