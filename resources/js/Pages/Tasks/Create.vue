<template>
  <div>
    <Head title="Utwórz zadanie" />
    <h1 class="mb-8 text-3xl font-bold">
      <Link class="text-indigo-400 hover:text-indigo-600" href="/tasks">Zadnie</Link>
      <span class="text-indigo-400 font-medium">/</span> Utwórz
    </h1>
    <div class="max-w-3xl bg-white rounded-md shadow overflow-hidden">
      <form @submit.prevent="store">
        <div class="flex flex-wrap -mb-8 -mr-6 p-8">
          <text-input
            v-model="form.title"
            :error="form.errors.title"
            class="pb-8 pr-6 w-full lg:w-1/2"
            label="Tytuł"
          />
          <select-input
            v-model="form.status"
            :error="form.errors.status"
            class="pb-8 pr-6 w-full lg:w-1/2"
            label="Status"
          >
            <option v-for="opt in statusOptions" :key="opt.value" :value="opt.value">
              {{ opt.label }}
            </option>
          </select-input>
          <textarea-input
            v-model="form.description"
            :error="form.errors.description"
            class="pb-8 pr-6 w-full lg:w-1/2"
            label="Opis"
          />
          <text-input
            v-model="form.due_date"
            :error="form.errors.due_date"
            class="pb-8 pr-6 w-full lg:w-1/2"
            type="date"
            label="Data zakończenia"
          />

        </div>
        <div class="flex items-center justify-end px-8 py-4 bg-gray-50 border-t border-gray-100">
          <loading-button :loading="form.processing" class="btn-indigo" type="submit">Zapisz</loading-button>
        </div>
      </form>
    </div>
  </div>
</template>

<script>
import { Head, Link } from '@inertiajs/vue3'
import Layout from '@/Shared/Layout.vue'
import TextInput from '@/Shared/TextInput.vue'
import SelectInput from '@/Shared/SelectInput.vue'
import LoadingButton from '@/Shared/LoadingButton.vue'
import TextareaInput from '../../Shared/TextareaInput.vue'

export default {
  components: {
    TextareaInput,
    Head,
    Link,
    LoadingButton,
    SelectInput,
    TextInput,
  },
  layout: Layout,
  remember: 'form',
  props: {
    statusOptions: {
      type: Array,
      default: () => [],
    },
  },
  data() {
    return {
      form: this.$inertia.form({
        title: '',
        description: '',
        status: 'pending',
        due_date: '',
      }),
    }
  },
  methods: {
    store() {
      this.form.post('/tasks')
    },
  },
}
</script>
