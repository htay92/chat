export type ChatResponder = (
    message: string,
    signal: AbortSignal,
) => Promise<string>;
export interface ChatMessage {
    id: number;
    role: "user" | "assistant";
    text: string;
}
