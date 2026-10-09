import { expect, it } from "vitest";
import { validateMessage } from "../../resources/js/chatbot/validation";
it("AC-03 trims edges and preserves internal newlines", () => {
    expect(validateMessage("  hello\nworld  ")).toEqual({
        text: "hello\nworld",
        error: "",
    });
});
it.each(["", "  ", "\n\t"])("AC-03 rejects blank %j", (input) => {
    expect(validateMessage(input).error).not.toBe("");
});
it("AC-04 counts code points rather than UTF-16 units", () => {
    expect(validateMessage("😀".repeat(1000)).error).toBe("");
    expect(validateMessage("😀".repeat(1001)).error).not.toBe("");
});
it("AC-04 counts combining characters and trimmed boundaries", () => {
    expect(validateMessage(" x ").text).toBe("x");
    expect(validateMessage("e\u0301".repeat(500)).error).toBe("");
    expect(validateMessage("e\u0301".repeat(500) + "x").error).not.toBe("");
});

it("005 AC-02 uses the same Unicode trimming fixtures as Laravel", async () => {
    const { readFileSync } = await import("node:fs");
    const fixtures = JSON.parse(readFileSync("tests/fixtures/chatbot-unicode.json", "utf8")) as { input: string; text: string; valid: boolean }[];
    for (const fixture of fixtures) {
        const result = validateMessage(fixture.input);
        expect(result.text).toBe(fixture.text);
        expect(result.error === "").toBe(fixture.valid);
    }
});
