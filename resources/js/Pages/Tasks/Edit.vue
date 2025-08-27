<template>
  <div>
    <Head :title="`Edytuj: ${form.title || 'Zadanie'}`" />
    <div class="flex justify-start mb-8 max-w-3xl">
      <h1 class="text-3xl font-bold">
        <Link class="text-indigo-400 hover:text-indigo-600" href="/tasks">Zadanie</Link>
        <span class="text-indigo-400 font-medium">/</span>
        {{ form.title || 'Zadanie' }}
      </h1>
      <img v-if="task.photo" class="block ml-4 w-8 h-8 rounded-full" :src="task.photo" alt="photo"/>
    </div>
    <trashed-message v-if="task.deleted_at" class="mb-6" @restore="restore"> To zadanie zostało usunięte. </trashed-message>
    <div class="max-w-3xl bg-white rounded-md shadow overflow-hidden">
      <form @submit.prevent="update">
        <div class="flex flex-wrap -mb-8 -mr-6 p-8">
          <text-input v-model="form.title" :error="form.errors.title" class="pb-8 pr-6 w-full lg:w-1/2" label="Tytuł" />
          <select-input v-model="form.status" :error="form.errors.status" class="pb-8 pr-6 w-full lg:w-1/2" label="Status">
            <option v-for="opt in statusOptions" :key="opt.value" :value="opt.value">
              {{ opt.label }}
            </option>
          </select-input>
          <textarea-input v-model="form.description" :error="form.errors.description" class="pb-8 pr-6 w-full" label="Opis" />
          <text-input v-model="form.due_date" :error="form.errors.due_date" class="pb-8 pr-6 w-full lg:w-1/2" type="date" label="Data zakończenia" />
        </div>
        <div class="flex items-center px-8 py-4 bg-gray-50 border-t border-gray-100">
          <button v-if="!task.deleted_at" class="text-red-600 hover:underline" tabindex="-1" type="button" @click="destroy">Usuń zadanie</button>
          <loading-button v-if="!task.deleted_at" :loading="form.processing" class="btn-indigo ml-auto" type="submit">Zaktualizuj zadanie</loading-button>
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
import TrashedMessage from '@/Shared/TrashedMessage.vue'
import TextareaInput from '@/Shared/TextareaInput.vue'

export default {
  components: {
    Head,
    Link,
    LoadingButton,
    SelectInput,
    TextInput,
    TrashedMessage,
    TextareaInput,
  },
  layout: Layout,
  props: {
    task: Object,
    statusOptions: {
      type: Array,
      default: () => [],
    },
  },
  remember: 'form',
  data() {
    return {
      form: this.$inertia.form({
        _method: 'put',
        title: this.task.title,
        description: this.task.description || '',
        status: this.task.status,
        due_date: this.task.due_date || '',
      }),
    }
  },
  methods: {
    update() {
      this.form.post(`/tasks/${this.task.uuid}`)
    },
    destroy() {
      if (confirm('Chcesz usunąć to zadanie?')) {
        this.$inertia.delete(`/tasks/${this.task.uuid}`)
      }
    },
    restore() {
      if (confirm('Chcesz przywrócić to zadanie?')) {
        this.$inertia.put(`/tasks/${this.task.uuid}/restore`)
      }
    },
  },
}
</script>
