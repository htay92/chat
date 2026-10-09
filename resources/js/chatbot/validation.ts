export function validateMessage(input: string): {
    text: string;
    error: string;
} {
    const text = input.trim();
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
