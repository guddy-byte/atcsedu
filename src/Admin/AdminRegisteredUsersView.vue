<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import { RouterLink } from 'vue-router'

import { apiRequest } from '../lib/api'

interface RegisteredUser {
  id: number
  name: string
  email: string
  registered_at: string | null
}

interface RegisteredUsersResponse {
  status: string
  data: {
    users: RegisteredUser[]
  }
}

interface DeleteUserResponse {
  status: string
  message: string
}

const users = ref<RegisteredUser[]>([])
const searchQuery = ref('')
const errorMessage = ref('')
const successMessage = ref('')
const isLoading = ref(false)
const deletingUserId = ref<number | null>(null)

const dateTimeFormatter = new Intl.DateTimeFormat('en-NG', {
  dateStyle: 'medium',
  timeStyle: 'short',
})

const filteredUsers = computed(() => {
  const query = searchQuery.value.trim().toLowerCase()

  if (!query) {
    return users.value
  }

  return users.value.filter((user) =>
    [user.name, user.email].join(' ').toLowerCase().includes(query),
  )
})

const loadUsers = async () => {
  isLoading.value = true
  errorMessage.value = ''

  try {
    const response = await apiRequest<RegisteredUsersResponse>('/admin/users')
    users.value = response.data.users
  } catch (error) {
    errorMessage.value = error instanceof Error ? error.message : 'Unable to load registered users.'
  } finally {
    isLoading.value = false
  }
}

const formatRegisteredAt = (value: string | null) => {
  if (!value) {
    return 'Unknown'
  }

  const date = new Date(value)

  if (Number.isNaN(date.getTime())) {
    return 'Unknown'
  }

  return dateTimeFormatter.format(date)
}

const deleteUser = async (user: RegisteredUser) => {
  const confirmed = confirm(`Delete ${user.name} from the platform? This action cannot be undone.`)

  if (!confirmed) {
    return
  }

  deletingUserId.value = user.id
  errorMessage.value = ''
  successMessage.value = ''

  try {
    const response = await apiRequest<DeleteUserResponse>(`/admin/users/${user.id}`, {
      method: 'DELETE',
    })

    users.value = users.value.filter((entry) => entry.id !== user.id)
    successMessage.value = response.message
  } catch (error) {
    errorMessage.value = error instanceof Error ? error.message : 'Unable to delete this user.'
  } finally {
    deletingUserId.value = null
  }
}

onMounted(() => {
  void loadUsers()
})
</script>

<template>
  <section class="grid gap-6">
    <div
      v-if="errorMessage"
      class="rounded-2xl border border-rose-200 bg-rose-50 px-5 py-4 text-sm font-medium text-rose-700"
    >
      {{ errorMessage }}
    </div>

    <div
      v-if="successMessage"
      class="rounded-2xl border border-emerald-200 bg-emerald-50 px-5 py-4 text-sm font-medium text-emerald-700"
    >
      {{ successMessage }}
    </div>

    <section class="rounded-[2.5rem] border border-slate-100 bg-white p-8 shadow-[0_20px_60px_rgba(0,0,0,0.03)]">
      <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
        <div class="max-w-3xl">
          <p class="text-xs font-semibold uppercase tracking-[0.22em] text-secondary">
            Admin users
          </p>
          <h2 class="mt-2 text-3xl font-black tracking-tight text-slate-950">
            Registered Users List
          </h2>
          <p class="mt-3 text-sm leading-7 text-slate-600">
            Review every registered student account, search quickly by name or email, and remove users who do not follow platform rules.
          </p>
        </div>

        <RouterLink
          to="/admin"
          class="inline-flex items-center justify-center rounded-full border border-slate-200 bg-white px-4 py-2.5 text-xs font-black uppercase tracking-[0.12em] text-slate-600 transition hover:border-primary/30 hover:text-primary"
        >
          Back to dashboard
        </RouterLink>
      </div>

      <div class="mt-6 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div class="flex flex-wrap items-center gap-3">
          <div class="relative w-full max-w-[260px]">
            <input
              v-model="searchQuery"
              type="search"
              placeholder="Search by name or email"
              class="w-full rounded-2xl border border-slate-200 bg-slate-50/60 py-2.5 pl-10 pr-4 text-sm font-semibold text-slate-700 outline-none transition focus:border-primary/30 focus:ring-4 focus:ring-primary/5"
            />
            <svg
              class="absolute left-3.5 top-1/2 h-3.5 w-3.5 -translate-y-1/2 text-slate-400"
              fill="none"
              stroke="currentColor"
              viewBox="0 0 24 24"
            >
              <path
                stroke-linecap="round"
                stroke-linejoin="round"
                stroke-width="3"
                d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"
              />
            </svg>
          </div>

          <button
            type="button"
            class="inline-flex items-center justify-center rounded-full border border-slate-200 bg-white px-4 py-2.5 text-xs font-black uppercase tracking-[0.12em] text-slate-600 transition hover:border-primary/30 hover:text-primary"
            @click="void loadUsers()"
          >
            Refresh list
          </button>
        </div>

        <p class="text-[11px] font-bold uppercase tracking-[0.14em] text-slate-400">
          {{ filteredUsers.length }} users shown
        </p>
      </div>

      <div class="mt-6 overflow-x-auto">
        <table class="w-full min-w-[680px] text-left">
          <thead>
            <tr class="border-b border-slate-100 text-[10px] font-black uppercase tracking-[0.16em] text-slate-400">
              <th class="px-4 py-4">Full Name</th>
              <th class="px-4 py-4">Email</th>
              <th class="px-4 py-4">Registered On</th>
              <th class="px-4 py-4 text-right">Actions</th>
            </tr>
          </thead>

          <tbody v-if="!isLoading" class="divide-y divide-slate-50">
            <tr
              v-for="user in filteredUsers"
              :key="user.id"
              class="transition hover:bg-slate-50/60"
            >
              <td class="px-4 py-5">
                <p class="text-sm font-black text-slate-900">{{ user.name }}</p>
              </td>
              <td class="px-4 py-5 text-sm font-semibold text-slate-600">
                {{ user.email }}
              </td>
              <td class="px-4 py-5 text-sm font-semibold text-slate-600">
                {{ formatRegisteredAt(user.registered_at) }}
              </td>
              <td class="px-4 py-5 text-right">
                <button
                  type="button"
                  :disabled="deletingUserId === user.id"
                  class="inline-flex items-center justify-center rounded-xl border border-rose-200 bg-white px-3 py-2 text-xs font-black uppercase tracking-[0.12em] text-rose-600 transition hover:bg-rose-50 disabled:cursor-not-allowed disabled:opacity-50"
                  @click="void deleteUser(user)"
                >
                  {{ deletingUserId === user.id ? 'Deleting...' : 'Delete User' }}
                </button>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <div
        v-if="isLoading"
        class="flex items-center justify-center py-16 text-sm font-semibold text-slate-500"
      >
        Loading registered users...
      </div>

      <div
        v-else-if="filteredUsers.length === 0"
        class="flex flex-col items-center justify-center py-16 text-center"
      >
        <p class="text-sm font-bold text-slate-700">
          {{ searchQuery ? 'No users matched your search.' : 'No registered student users yet.' }}
        </p>
        <p class="mt-2 text-sm text-slate-500">
          {{ searchQuery ? 'Try a different name or email keyword.' : 'New student registrations will appear here automatically.' }}
        </p>
      </div>
    </section>
  </section>
</template>
