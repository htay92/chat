export function trimUnicode(input: string): string {
    return input.replace(/^[\p{Z}\u0009-\u000D\u0085\uFEFF]+|[\p{Z}\u0009-\u000D\u0085\uFEFF]+$/gu, "");
}
export function validateMessage(input: string): {
    text: string;
    error: string;
} {
    const text = trimUnicode(input);
    const length = Array.from(text).length;
    return {
        text,
        error:
            length === 0
                ? "Please enter a message."
                : length > 1000
                  ? "Use 1,000 characters or fewer."
                  : "",
    };
}
