<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps<{
    ticket: {
        id: number;
        subject: string;
        priority: string;
        status: string;
        escalated_at: string | null;
        customer: any;
        agent: any;
        escalation_notifications: any[];
    };
}>();

const loading = ref(false);
const message = ref('');
const error = ref('');

const escalate = async () => {
    loading.value = true;
    message.value = '';
    error.value = '';

    try {
        const response = await fetch(`/api/tickets/${props.ticket.id}/escalate`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify({
                channels: ['email', 'slack']
            })
        });

        const data = await response.json();

        if (response.status === 202) {
            message.value = 'Ticket escalation initiated successfully!';
            router.reload({ only: ['ticket'] });
        } else {
            error.value = data.message || 'Escalation failed.';
        }
    } catch (e) {
        error.value = 'An error occurred during escalation.';
    } finally {
        loading.value = false;
    }
};
</script>

<template>
    <Head title="Ticket Escalation" />

    <div class="max-w-2xl mx-auto my-12 p-8 bg-white rounded-lg shadow border border-gray-200">
        <h1 class="text-3xl font-bold mb-6 text-gray-900 border-b pb-4">Ticket Escalation Details</h1>

        <div class="space-y-4 mb-6">
            <div class="flex border-b pb-2">
                <span class="w-1/3 font-semibold text-gray-600">Ticket ID:</span>
                <span class="w-2/3 text-gray-900">#{{ ticket.id }}</span>
            </div>
            <div class="flex border-b pb-2">
                <span class="w-1/3 font-semibold text-gray-600">Subject:</span>
                <span class="w-2/3 text-gray-900">{{ ticket.subject }}</span>
            </div>
            <div class="flex border-b pb-2">
                <span class="w-1/3 font-semibold text-gray-600">Priority:</span>
                <span class="w-2/3 text-gray-900 capitalize font-medium">{{ ticket.priority }}</span>
            </div>
            <div class="flex border-b pb-2">
                <span class="w-1/3 font-semibold text-gray-600">Status:</span>
                <span class="w-2/3 text-gray-900 capitalize font-medium">
                    <span :class="ticket.status === 'escalated' ? 'text-red-600' : 'text-green-600'">
                        {{ ticket.status }}
                    </span>
                </span>
            </div>
            <div class="flex border-b pb-2">
                <span class="w-1/3 font-semibold text-gray-600">Escalated At:</span>
                <span class="w-2/3 text-gray-900">
                    {{ ticket.escalated_at ? new Date(ticket.escalated_at).toLocaleString() : 'Not Escalated' }}
                </span>
            </div>
        </div>

        <div class="mt-6">
            <button
                @click="escalate"
                :disabled="loading || ticket.status === 'escalated'"
                class="px-6 py-3 bg-red-600 text-white font-semibold rounded shadow hover:bg-red-700 disabled:bg-gray-300 disabled:cursor-not-allowed transition"
            >
                {{ loading ? 'Escalating...' : 'Escalate' }}
            </button>
        </div>

        <div v-if="message" class="mt-6 p-4 bg-green-50 border border-green-200 text-green-800 rounded">
            {{ message }}
        </div>

        <div v-if="error" class="mt-6 p-4 bg-red-50 border border-red-200 text-red-800 rounded">
            {{ error }}
        </div>
    </div>
</template>
