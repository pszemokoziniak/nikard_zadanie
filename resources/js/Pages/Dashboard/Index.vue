<template>
  <div>
    <Head title="Dashboard" />
    <h1 class="mb-6 text-2xl font-bold">Api demo</h1>

    <div class="grid gap-6 md:grid-cols-2">
      <div class="bg-white rounded-md shadow p-4">
        <h2 class="text-lg font-semibold mb-2">REST: Przykładowe posty</h2>
        <ul v-if="restPosts && restPosts.length" class="list-disc ml-5 space-y-1">
          <li v-for="post in restPosts" :key="post.id">
            <span class="font-medium">#{{ post.id }}</span> — {{ post.title }}
          </li>
        </ul>
        <p v-else class="text-gray-500">Brak danych z REST.</p>
        <p v-if="restError" class="mt-2 text-red-600 text-sm">Błąd REST: {{ restError }}</p>
      </div>

      <div class="bg-white rounded-md shadow p-4">
        <h2 class="text-lg font-semibold mb-2">SOAP: Stolica (PL)</h2>
        <p v-if="soapCapital" class="text-gray-800">
          Stolica Polski: <span class="font-medium">{{ soapCapital }}</span>
        </p>
        <p v-else class="text-gray-500">Brak danych z SOAP.</p>
        <p v-if="soapError" class="mt-2 text-red-600 text-sm">Błąd SOAP: {{ soapError }}</p>
      </div>
    </div>
  </div>
</template>

<script>
import { Head } from '@inertiajs/vue3'
import Layout from '@/Shared/Layout.vue'

export default {
  components: { Head },
  layout: Layout,
  props: {
    restPosts: { type: Array, default: () => [] },
    soapCapital: { type: [String, null], default: null },
    restError: { type: [String, null], default: null },
    soapError: { type: [String, null], default: null },
  },
}
</script>
