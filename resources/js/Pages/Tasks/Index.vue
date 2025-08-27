<template>
  <div>
    <Head title="Zadania" />
    <h1 class="mb-8 text-3xl font-bold">Zadania</h1>
    <div class="flex items-center justify-between mb-6">
      <search-filter v-model="form.search" class="mr-4 w-full max-w-md" @reset="reset">
        <label class="block text-gray-700">Status:</label>
        <select v-model="form.status" class="form-select mt-1 w-full">
          <option :value="null" />
          <option value="pending">Oczekujące</option>
          <option value="in_progress">W toku</option>
          <option value="done">Zrobione</option>
        </select>
        <label class="block mt-4 text-gray-700">Usunięte:</label>
        <select v-model="form.trashed" class="form-select mt-1 w-full">
          <option :value="null" />
          <option value="with">Wszystkie</option>
          <option value="only">Tylko usunięte</option>
        </select>
      </search-filter>
      <Link class="btn-indigo" href="/tasks/create">
        <span>Utwórz</span>
        <span class="hidden md:inline">&nbsp;Zadanie</span>
      </Link>
    </div>
    <div class="bg-white rounded-md shadow overflow-x-auto">
      <table class="w-full whitespace-normal">
        <tr class="text-left font-bold">
          <th class="pb-4 pt-6 px-6">Tytuł</th>
          <th class="pb-4 pt-6 px-6">Opis</th>
          <th class="pb-4 pt-6 px-6">Status</th>
          <th class="pb-4 pt-6 px-6">Data wykonania</th>
          <th class="pb-4 pt-6 px-6"></th>
        </tr>
        <tr v-for="task in (tasks?.data ?? [])" :key="task.uuid ?? task.id" class="hover:bg-gray-100 focus-within:bg-gray-100">
          <td class="border-t">
            <Link v-if="task.can?.update"
                  class="flex min-w-0 items-center px-6 py-4 focus:text-indigo-500 break-words"
                  :href="`/tasks/${task.uuid ?? task.id}/edit`">
              {{ task.title }}
              <icon v-if="task.deleted_at" name="trash" class="shrink-0 ml-2 w-3 h-3 fill-gray-400" />
            </Link>
            <span v-else class="flex min-w-0 items-center px-6 py-4 text-gray-700 break-words">
              {{ task.title }}
              <icon v-if="task.deleted_at" name="trash" class="shrink-0 ml-2 w-3 h-3 fill-gray-400" />
            </span>
          </td>
          <td class="border-t">
            <Link v-if="task.can?.update"
                  class="flex min-w-0 items-center px-6 py-4 break-words"
                  :href="`/tasks/${task.uuid ?? task.id}/edit`" tabindex="-1">
              {{ task.description }}
            </Link>
            <span v-else class="flex min-w-0 items-center px-6 py-4 break-words" tabindex="-1">
              {{ task.description }}
            </span>
          </td>
          <td class="border-t">
            <Link v-if="task.can?.update"
                  class="flex min-w-0 items-center px-6 py-4 break-words"
                  :href="`/tasks/${task.uuid ?? task.id}/edit`" tabindex="-1">
              {{ prettyStatus(task.status) }}
            </Link>
            <span v-else class="flex min-w-0 items-center px-6 py-4 break-words" tabindex="-1">
              {{ prettyStatus(task.status) }}
            </span>
          </td>
          <td class="border-t">
            <Link v-if="task.can?.update"
                  class="flex min-w-0 items-center px-6 py-4 break-words"
                  :href="`/tasks/${task.uuid ?? task.id}/edit`" tabindex="-1">
              {{ task.due_date ?? '—' }}
            </Link>
            <span v-else class="flex min-w-0 items-center px-6 py-4 break-words" tabindex="-1">
              {{ task.due_date ?? '—' }}
            </span>
          </td>
          <td class="w-px border-t">
            <Link v-if="task.can?.update"
                  class="flex items-center px-4"
                  :href="`/tasks/${task.uuid ?? task.id}/edit`" tabindex="-1">
              <icon name="cheveron-right" class="block w-6 h-6 fill-gray-400" />
            </Link>
            <span v-else class="flex items-center px-4" tabindex="-1">
              <icon name="cheveron-right" class="block w-6 h-6 fill-gray-300" />
            </span>
          </td>
        </tr>
        <tr v-if="(tasks?.data?.length ?? 0) === 0">
          <td class="px-6 py-4 border-t" colspan="5">Brak zadań</td>
        </tr>
      </table>
    </div>
    <pagination v-if="tasks?.links" class="mt-6" :links="tasks.links" />
  </div>
</template>

<script>
import { Head, Link } from '@inertiajs/vue3'
import Icon from '@/Shared/Icon.vue'
import pickBy from 'lodash/pickBy'
import Layout from '@/Shared/Layout.vue'
import throttle from 'lodash/throttle'
import mapValues from 'lodash/mapValues'
import SearchFilter from '@/Shared/SearchFilter.vue'
import Pagination from '@/Shared/Pagination.vue'

export default {
  components: {
    Head,
    Icon,
    Link,
    SearchFilter,
    Pagination,
  },
  layout: Layout,
  props: {
    filters: Object,
    tasks: {
      type: Object, // paginator
      default: () => ({ data: [], links: [] }),
    },
  },
  data() {
    return {
      form: {
        search: this.filters?.search ?? '',
        status: this.filters?.status ?? null,
        trashed: this.filters?.trashed ?? null,
      },
    }
  },
  mounted() {
    window.addEventListener('inertia:error', this.onInertiaError)
  },
  beforeUnmount() {
    window.removeEventListener('inertia:error', this.onInertiaError)
  },
  watch: {
    form: {
      deep: true,
      handler: throttle(function () {
        this.$inertia.get('/tasks', pickBy(this.form), { preserveState: true })
      }, 150),
    },
  },
  methods: {
    reset() {
      this.form = mapValues(this.form, () => null)
    },
    prettyStatus(s) {
      if (s === 'pending') return 'Oczekujące'
      if (s === 'in_progress') return 'W toku'
      if (s === 'done') return 'Zrobione'
      return s || '—'
    },
    onInertiaError(event) {
      const status = event?.detail?.response?.status
      if (status === 403) {
        alert('Brak uprawnień do wykonania tej akcji.')
        this.$inertia.visit('/tasks', { replace: true })
      }
    },
  },
}
</script>
