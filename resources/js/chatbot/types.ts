export interface ExchangeContext { sessionId: string; requestId: string; }
export type ChatResponder = (
    message: string,
    signal: AbortSignal,
    context?: Readonly<ExchangeContext>,
) => Promise<string>;
export interface ChatMessage {
    id: number;
    role: "user" | "assistant";
    text: string;
}
