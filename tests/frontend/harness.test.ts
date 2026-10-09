import { expect, it } from "vitest";
import { ref } from "vue";
it("runs Vue reactive tests", () => {
    expect(ref(true).value).toBe(true);
});
