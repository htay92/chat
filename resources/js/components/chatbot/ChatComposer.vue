<script setup lang="ts">
const props = defineProps<{
    draft: string;
    pending: boolean;
    error: string;
    inputId: string;
}>();
const emit = defineEmits<{ "update:draft": [value: string]; submit: [] }>();
function keydown(event: KeyboardEvent) {
    if (
        event.key !== "Enter" ||
        event.shiftKey ||
        event.isComposing ||
        event.keyCode === 229
    )
        return;
    event.preventDefault();
    if (!props.pending) emit("submit");
}
</script>
<template>
    <form class="chat-composer" @submit.prevent="!pending && emit('submit')">
        <label :for="inputId">Message</label>
        <textarea
            :id="inputId"
            :value="draft"
            :disabled="pending"
            :aria-invalid="Boolean(error)"
            :aria-describedby="error ? `${inputId}-error` : undefined"
            rows="2"
            @input="
                emit(
                    'update:draft',
                    ($event.target as HTMLTextAreaElement).value,
                )
            "
            @keydown="keydown"
        />
        <p v-if="error" :id="`${inputId}-error`" class="chat-validation">
            {{ error }}
        </p>
        <button class="chat-button" type="submit" :disabled="pending">
            Send
        </button>
    </form>
</template>
