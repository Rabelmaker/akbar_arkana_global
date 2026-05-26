<script setup>
import axios from '@/lib/http'
import { useAuthStore } from '@/stores/auth'
import ConfirmDialog from '@/components/dialogs/ConfirmDialog.vue'
import { emitManagementUpdated, useManagementUpdateListener } from '@/composables/useManagementRealtime'
import RoleForm from '@/pages/apps/roles/RoleForm.vue'
import poseM from '@images/pages/pose_m1.png'

const authStore = useAuthStore()

const roles = ref([])
const permissions = ref([])
const users = ref([])
const isLoading = ref(false)
const isSaving = ref(false)
const isDialogVisible = ref(false)
const selectedRole = ref(null)
const isDeleteDialogVisible = ref(false)
const roleToDelete = ref(null)
const formErrors = ref({})
const pageError = ref('')

const loadRoles = async () => {
  isLoading.value = true

  try {
    pageError.value = ''

    const [rolesResponse, usersResponse] = await Promise.all([
      axios.get('/api/roles', {
        params: {
          per_page: 100,
          page: 1,
        },
      }),
      axios.get('/api/roles/users'),
    ])

    const rolePayload = rolesResponse.data?.data ?? {}
    users.value = usersResponse.data?.users ?? []
    permissions.value = (rolePayload.permissions ?? []).map(permission => ({
      title: permission.name,
      value: permission.name,
    }))
    roles.value = (rolePayload.roles ?? []).map(role => {
      const roleUsers = users.value.filter(user => (user.roles ?? []).includes(role.name))

      return {
        id: role.id,
        name: role.name,
        permissions: (role.permissions ?? []).map(permission => permission.name),
        users_count: roleUsers.length,
        users_preview: roleUsers.slice(0, 4),
      }
    })
  }
  catch {
    roles.value = []
    permissions.value = []
    users.value = []
    pageError.value = 'Unable to load roles right now.'
  }
  finally {
    isLoading.value = false
  }
}

const openCreateDialog = () => {
  selectedRole.value = null
  formErrors.value = {}
  isDialogVisible.value = true
}

const openEditDialog = role => {
  formErrors.value = {}
  selectedRole.value = {
    id: role.id,
    name: role.name,
    permissions: [...role.permissions],
  }
  isDialogVisible.value = true
}

const submitRole = async payload => {
  formErrors.value = {}
  pageError.value = ''
  isSaving.value = true

  try {
    await authStore.ensureCsrfCookie()

    if (payload.id) {
      await axios.put(`/api/roles/${payload.id}`, {
        name: payload.name,
        permissions: payload.permissions,
      })
    }
    else {
      await axios.post('/api/roles', {
        name: payload.name,
        permissions: payload.permissions,
      })
    }

    isDialogVisible.value = false
    await loadRoles()
    emitManagementUpdated({ entity: 'roles', source: 'roles' })
  }
  catch (error) {
    if (error?.response?.status === 422) {
      formErrors.value = error.response.data?.errors ?? {}
      return
    }

    pageError.value = error?.response?.data?.message ?? 'Unable to save role right now.'
  }
  finally {
    isSaving.value = false
  }
}

const askDeleteRole = role => {
  roleToDelete.value = role
  isDeleteDialogVisible.value = true
}

const deleteRole = async confirmed => {
  if (!confirmed || !roleToDelete.value)
    return

  try {
    pageError.value = ''
    await authStore.ensureCsrfCookie()
    await axios.delete(`/api/roles/${roleToDelete.value.id}`)
    await loadRoles()
    emitManagementUpdated({ entity: 'roles', source: 'roles' })
  }
  catch (error) {
    pageError.value = error?.response?.data?.message ?? 'Unable to delete role.'
  }
  finally {
    roleToDelete.value = null
  }
}

const avatarColor = index => ['primary', 'success', 'warning', 'info', 'error'][index % 5]

useManagementUpdateListener(({ source }) => {
  if (source === 'roles')
    return

  loadRoles()
})

onMounted(loadRoles)
</script>

<template>
  <div>
    <VAlert
      v-if="pageError"
      type="error"
      variant="tonal"
      class="mb-6"
    >
      {{ pageError }}
    </VAlert>

    <VRow>
    <VCol
      v-for="(item, index) in roles"
      :key="item.id"
      cols="12"
      sm="6"
      lg="4"
    >
      <VCard>
        <VCardText class="d-flex align-center pb-4">
          <span>Total {{ item.users_count }} users</span>

          <VSpacer />

          <div class="v-avatar-group">
            <template
              v-for="(user, userIndex) in item.users_preview"
              :key="user.id"
            >
              <VAvatar
                v-if="item.users_preview.length > 4 && userIndex < 3"
                size="40"
                :color="avatarColor(userIndex)"
                variant="tonal"
              >
                <span>{{ avatarText(user.name) }}</span>
              </VAvatar>

              <VAvatar
                v-else-if="item.users_preview.length <= 4"
                size="40"
                :color="avatarColor(userIndex)"
                variant="tonal"
              >
                <span>{{ avatarText(user.name) }}</span>
              </VAvatar>
            </template>

            <VAvatar
              v-if="item.users_count > 4"
              :color="$vuetify.theme.current.dark ? '#383B55' : '#F0EFF0'"
            >
              <span class="text-high-emphasis">
                +{{ item.users_count - 3 }}
              </span>
            </VAvatar>
          </div>
        </VCardText>

        <VCardText>
          <div class="d-flex justify-space-between align-end">
            <div>
              <h5 class="text-h5 mb-1">
                {{ item.name }}
              </h5>
              <a
                v-if="authStore.can('roles.edit')"
                href="javascript:void(0)"
                @click="openEditDialog(item)"
              >
                Edit Role
              </a>
            </div>

            <div class="d-flex align-center gap-2">
              <IconBtn
                v-if="authStore.can('roles.delete')"
                color="secondary"
                class="mt-n2"
                @click="askDeleteRole(item)"
              >
                <VIcon icon="ri-delete-bin-7-line" />
              </IconBtn>
            </div>
          </div>
        </VCardText>
      </VCard>
    </VCol>

    <VCol
      v-if="authStore.can('roles.create')"
      cols="12"
      sm="6"
      lg="4"
    >
      <VCard
        class="h-100"
        :ripple="false"
      >
        <VRow
          no-gutters
          class="h-100"
        >
          <VCol
            cols="5"
            class="d-flex flex-column justify-end align-center"
          >
            <img
              width="69"
              :src="poseM"
              alt="add-role"
            >
          </VCol>

          <VCol cols="7">
            <VCardText class="d-flex flex-column align-end justify-end gap-4">
              <VBtn
                size="small"
                @click="openCreateDialog"
              >
                Add Role
              </VBtn>
              <span class="text-end">Add new role, if it doesn't exist.</span>
            </VCardText>
          </VCol>
        </VRow>
      </VCard>
    </VCol>
    </VRow>

    <RoleForm
      v-model:is-dialog-visible="isDialogVisible"
      :role="selectedRole"
      :permissions="permissions"
      :errors="formErrors"
      :loading="isSaving"
      @submit="submitRole"
    />

    <ConfirmDialog
      v-model:is-dialog-visible="isDeleteDialogVisible"
      confirmation-question="Delete this role?"
      confirm-title="Deleted!"
      confirm-msg="The role has been removed successfully."
      cancel-title="Cancelled"
      cancel-msg="The role deletion has been cancelled."
      @confirm="deleteRole"
    />
  </div>
</template>
