<script setup>
import axios from '@/lib/http'
import { emitManagementUpdated, useManagementUpdateListener } from '@/composables/useManagementRealtime'
import { useAuthStore } from '@/stores/auth'
import ConfirmDialog from '@/components/dialogs/ConfirmDialog.vue'
import jsPDF from 'jspdf'
import autoTable from 'jspdf-autotable'
import UserForm from '@/views/apps/users/UserForm.vue'
import * as XLSX from 'xlsx'

const authStore = useAuthStore()

const users = ref([])
const roles = ref([])
const isLoading = ref(false)
const isSaving = ref(false)
const pageError = ref('')
const formErrors = ref({})
const searchQuery = ref('')
const selectedRole = ref(null)
const itemsPerPage = ref(10)
const page = ref(1)
const selectedRows = ref([])
const isFormVisible = ref(false)
const isDeleteDialogVisible = ref(false)
const selectedUser = ref(null)
const userToDelete = ref(null)
const totalUsers = ref(0)
const isExporting = ref(false)
const isExportMenuVisible = ref(false)

const headers = [
  {
    title: 'User',
    key: 'user',
  },
  {
    title: 'Email',
    key: 'email',
  },
  {
    title: 'Role',
    key: 'role',
  },
  {
    title: 'Actions',
    key: 'actions',
    sortable: false,
  },
]

const normalizeUsersResponse = response => {
  const payload = response.data?.data ?? {}
  const legacyUsers = response.data?.users ?? []
  const legacyStatusById = new Map(legacyUsers.map(user => [user.id, user.status]))

  return {
    users: (payload.users ?? []).map(user => ({
      id: user.id,
      name: user.name,
      email: user.email,
      role: user.roles?.[0]?.name ?? '-',
      roles: (user.roles ?? []).map(role => role.name),
      status: legacyStatusById.get(user.id) ?? 'Active',
    })),
    pagination: payload.pagination ?? {
      current_page: 1,
      per_page: itemsPerPage.value,
      total: 0,
      last_page: 1,
    },
    roles: (payload.roles ?? []).map(role => ({
      title: role,
      value: role,
    })),
  }
}

const fetchUsers = params => axios.get('/api/users', { params })

const loadUsers = async () => {
  isLoading.value = true

  try {
    pageError.value = ''

    const response = await fetchUsers({
      search: searchQuery.value || undefined,
      role: selectedRole.value || undefined,
      per_page: itemsPerPage.value,
      page: page.value,
    })

    const normalized = normalizeUsersResponse(response)

    users.value = normalized.users
    totalUsers.value = normalized.pagination.total ?? 0
    roles.value = normalized.roles
  }
  catch (error) {
    users.value = []
    totalUsers.value = 0
    roles.value = []
    pageError.value = error?.response?.data?.message ?? 'Unable to load users right now.'
  }
  finally {
    isLoading.value = false
  }
}

const avatarColor = index => ['primary', 'success', 'warning', 'info', 'error'][index % 5]

const resolveUserRoleVariant = role => {
  const roleLowerCase = role?.toLowerCase?.() ?? ''

  if (roleLowerCase === 'subscriber')
    return { color: 'success', icon: 'ri-user-line' }
  if (roleLowerCase === 'author')
    return { color: 'error', icon: 'ri-computer-line' }
  if (roleLowerCase === 'maintainer')
    return { color: 'info', icon: 'ri-pie-chart-line' }
  if (roleLowerCase === 'editor')
    return { color: 'warning', icon: 'ri-edit-box-line' }
  if (roleLowerCase === 'admin' || roleLowerCase === 'super admin')
    return { color: 'primary', icon: 'ri-vip-crown-line' }

  return { color: 'success', icon: 'ri-user-line' }
}

const openCreateForm = () => {
  selectedUser.value = null
  formErrors.value = {}
  isFormVisible.value = true
}

const openEditForm = user => {
  selectedUser.value = {
    id: user.id,
    name: user.name,
    email: user.email,
    role: user.role ?? user.roles?.[0] ?? null,
  }
  formErrors.value = {}
  isFormVisible.value = true
}

const submitUser = async payload => {
  formErrors.value = {}
  pageError.value = ''
  isSaving.value = true

  try {
    await authStore.ensureCsrfCookie()

    if (payload.id) {
      await axios.put(`/api/users/${payload.id}`, payload)
    }
    else {
      await axios.post('/api/users', payload)
    }

    isFormVisible.value = false
    await loadUsers()
    emitManagementUpdated({ entity: 'users', source: 'users' })
  }
  catch (error) {
    if (error?.response?.status === 422) {
      formErrors.value = error.response.data?.errors ?? {}
      return
    }

    pageError.value = error?.response?.data?.message ?? 'Unable to save user right now.'
  }
  finally {
    isSaving.value = false
  }
}

const askDeleteUser = user => {
  userToDelete.value = user
  isDeleteDialogVisible.value = true
}

const deleteUser = async confirmed => {
  if (!confirmed || !userToDelete.value)
    return

  try {
    pageError.value = ''
    await authStore.ensureCsrfCookie()
    await axios.delete(`/api/users/${userToDelete.value.id}`)
    await loadUsers()
    emitManagementUpdated({ entity: 'users', source: 'users' })
  }
  catch (error) {
    pageError.value = error?.response?.data?.message ?? 'Unable to delete user.'
  }
  finally {
    userToDelete.value = null
  }
}

const collectExportUsers = async () => {
  const selectedUsers = users.value.filter(user => selectedRows.value.includes(user.id))

  if (selectedUsers.length)
    return selectedUsers

  const exportUsers = []
  let currentPage = 1
  let lastPage = 1

  do {
    const response = await fetchUsers({
      search: searchQuery.value || undefined,
      role: selectedRole.value || undefined,
      per_page: 100,
      page: currentPage,
    })
    const normalized = normalizeUsersResponse(response)

    exportUsers.push(...normalized.users)
    lastPage = normalized.pagination.last_page ?? 1
    currentPage++
  } while (currentPage <= lastPage)

  return exportUsers
}

const mapExportRows = exportUsers => exportUsers.map(user => ({
  Name: user.name,
  Email: user.email,
  Role: user.role,
}))

const exportUsersToExcel = async () => {
  isExporting.value = true

  try {
    const exportUsers = await collectExportUsers()
    const worksheet = XLSX.utils.json_to_sheet(mapExportRows(exportUsers))
    const workbook = XLSX.utils.book_new()

    XLSX.utils.book_append_sheet(workbook, worksheet, 'Users')
    XLSX.writeFile(workbook, 'office-cms-users.xlsx')
  }
  finally {
    isExporting.value = false
    isExportMenuVisible.value = false
  }
}

const exportUsersToPdf = async () => {
  isExporting.value = true

  try {
    const exportUsers = await collectExportUsers()
    const doc = new jsPDF()

    autoTable(doc, {
      head: [['Name', 'Email', 'Role']],
      body: exportUsers.map(user => [user.name, user.email, user.role]),
    })

    doc.save('office-cms-users.pdf')
  }
  finally {
    isExporting.value = false
    isExportMenuVisible.value = false
  }
}

useManagementUpdateListener(({ source }) => {
  if (source === 'users')
    return

  loadUsers()
})

watch([searchQuery, selectedRole], async () => {
  page.value = 1
  await loadUsers()
})

watch([page, itemsPerPage], loadUsers)

onMounted(loadUsers)
</script>

<template>
  <section>
    <VAlert
      v-if="pageError"
      type="error"
      variant="tonal"
      class="mb-6"
    >
      {{ pageError }}
    </VAlert>

    <VCard>
      <VCardText class="d-flex flex-wrap gap-4 align-center">
        <VMenu v-model="isExportMenuVisible">
          <template #activator="{ props: menuProps }">
            <VBtn
              v-bind="menuProps"
              variant="outlined"
              color="secondary"
              prepend-icon="ri-upload-2-line"
              :loading="isExporting"
            >
              Export
            </VBtn>
          </template>

          <VList>
            <VListItem
              prepend-icon="ri-file-excel-2-line"
              title="Export Excel"
              @click="exportUsersToExcel"
            />
            <VListItem
              prepend-icon="ri-file-pdf-2-line"
              title="Export PDF"
              @click="exportUsersToPdf"
            />
          </VList>
        </VMenu>

        <VSpacer />

        <div class="d-flex align-center gap-4 flex-wrap">
          <div style="inline-size: 15.625rem;">
            <VTextField
              v-model="searchQuery"
              placeholder="Search User"
              density="compact"
            />
          </div>

          <div style="inline-size: 11.625rem;">
            <VSelect
              v-model="selectedRole"
              placeholder="Select Role"
              :items="roles"
              clearable
              clear-icon="ri-close-line"
            />
          </div>

          <VBtn
            v-if="authStore.can('users.create')"
            @click="openCreateForm"
          >
            Add New User
          </VBtn>
        </div>
      </VCardText>

      <VDataTableServer
        v-model:model-value="selectedRows"
        v-model:items-per-page="itemsPerPage"
        v-model:page="page"
        :headers="headers"
        :items="users"
        :items-length="totalUsers"
        :loading="isLoading"
        show-select
        item-value="id"
        class="text-no-wrap rounded-0"
      >
        <template #item.user="{ item, index }">
          <div class="d-flex align-center">
            <VAvatar
              size="34"
              :color="avatarColor(index)"
              variant="tonal"
              class="me-3"
            >
              <span>{{ avatarText(item.name) }}</span>
            </VAvatar>

            <div class="d-flex flex-column">
              <span class="font-weight-medium text-base text-high-emphasis">
                {{ item.name }}
              </span>
              <span class="text-sm text-medium-emphasis">
                @{{ item.email.split('@')[0] }}
              </span>
            </div>
          </div>
        </template>

        <template #item.role="{ item }">
          <div class="d-flex gap-2 align-center">
            <VIcon
              :icon="resolveUserRoleVariant(item.role).icon"
              :color="resolveUserRoleVariant(item.role).color"
              size="22"
            />
            <span class="text-capitalize text-high-emphasis">{{ item.role }}</span>
          </div>
        </template>

        <template #item.actions="{ item }">
          <div class="d-flex align-center">
            <IconBtn
              v-if="authStore.can('users.delete')"
              size="small"
              @click="askDeleteUser(item)"
            >
              <VIcon icon="ri-delete-bin-7-line" />
            </IconBtn>

            <IconBtn
              v-if="authStore.can('users.edit')"
              size="small"
              @click="openEditForm(item)"
            >
              <VIcon icon="ri-pencil-line" />
            </IconBtn>
          </div>
        </template>

        <template #bottom>
          <VDivider />

          <div class="d-flex justify-end flex-wrap gap-x-6 px-2 py-1">
            <div class="d-flex align-center gap-x-2 text-medium-emphasis text-base">
              Rows Per Page:
              <VSelect
                v-model="itemsPerPage"
                class="per-page-select"
                variant="plain"
                :items="[10, 20, 25, 50, 100]"
              />
            </div>

            <p class="d-flex align-center text-base text-high-emphasis me-2 mb-0">
              {{ paginationMeta({ page, itemsPerPage }, totalUsers) }}
            </p>

            <div class="d-flex gap-x-2 align-center me-2">
              <VBtn
                class="flip-in-rtl"
                icon="ri-arrow-left-s-line"
                variant="text"
                density="comfortable"
                color="high-emphasis"
                :disabled="page <= 1"
                @click="page <= 1 ? page = 1 : page--"
              />

              <VBtn
                class="flip-in-rtl"
                icon="ri-arrow-right-s-line"
                density="comfortable"
                variant="text"
                color="high-emphasis"
                :disabled="page >= Math.ceil(totalUsers / itemsPerPage)"
                @click="page >= Math.ceil(totalUsers / itemsPerPage) ? page = Math.ceil(totalUsers / itemsPerPage) : page++"
              />
            </div>
          </div>
        </template>
      </VDataTableServer>
    </VCard>

    <UserForm
      v-model:is-drawer-open="isFormVisible"
      :user="selectedUser"
      :roles="roles"
      :errors="formErrors"
      :loading="isSaving"
      @submit="submitUser"
    />

    <ConfirmDialog
      v-model:is-dialog-visible="isDeleteDialogVisible"
      confirmation-question="Delete this user?"
      confirm-title="Deleted!"
      confirm-msg="The user has been removed successfully."
      cancel-title="Cancelled"
      cancel-msg="The user deletion has been cancelled."
      @confirm="deleteUser"
    />
  </section>
</template>
