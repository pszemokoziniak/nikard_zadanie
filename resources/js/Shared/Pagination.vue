<template>
  <div v-if="mappedLinks.length > 3">
    <div class="flex flex-wrap -mb-1">
      <template v-for="(link, key) in mappedLinks" :key="key">
        <div
          v-if="link.url === null"
          class="mb-1 mr-1 px-4 py-3 text-gray-400 text-sm leading-4 border rounded"
          v-html="link.label"
        />
        <Link
          v-else
          :key="`link-${key}`"
          class="mb-1 mr-1 px-4 py-3 focus:text-indigo-500 text-sm leading-4 hover:bg-white border focus:border-indigo-500 rounded"
          :class="{ 'bg-white': link.active }"
          :href="link.url"
          v-html="link.label"
        />
      </template>
    </div>
  </div>
</template>

<script>
import { Link } from '@inertiajs/vue3'

export default {
  components: { Link },
  props: {
    links: {
      type: Array,
      default: () => [],
    },
  },
  computed: {
    mappedLinks() {
      return (this.links || []).map((l) => {
        const raw = this.decodeHtml(l.label).trim().toLowerCase()
        let label = l.label
        if (raw.includes('previous')) {
          label = '&laquo; Poprzednie'
        } else if (raw.includes('next')) {
          label = 'Następne &raquo;'
        }
        return { ...l, label }
      })
    },
  },
  methods: {
    decodeHtml(html) {
      const el = document.createElement('div')
      el.innerHTML = html
      return el.textContent || el.innerText || ''
    },
  },
}
</script>
