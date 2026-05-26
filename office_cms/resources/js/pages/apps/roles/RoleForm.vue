<script setup>
const props = defineProps({
  isDialogVisible: {
    type: Boolean,
    required: true,
  },
  role: {
    type: Object,
    default: () => null,
  },
  permissions: {
    type: Array,
    default: () => [],
  },
  errors: {
    type: Object,
    default: () => ({}),
  },
  loading: {
    type: Boolean,
    default: false,
  },
})

const emit = defineEmits([
  'update:isDialogVisible',
  'submit',
])

const form = reactive({
  name: '',
  permissions: [],
})

const permissionActions = [
  {
    key: 'read',
    label: 'Read',
  },
  {
    key: 'write',
    label: 'Write',
  },
  {
    key: 'create',
    label: 'Create',
  },
  {
    key: 'delete',
    label: 'Delete',
  },
]

const permissionMatrix = computed(() => {
  const availablePermissions = new Set(props.permissions.map(permission => permission.value))

  return [
    {
      key: 'dashboard',
      label: 'Dashboard Management',
      actions: {
        read: 'dashboard.view',
      },
    },
    {
      key: 'users',
      label: 'User Management',
      actions: {
        read: 'users.view',
        write: 'users.edit',
        create: 'users.create',
        delete: 'users.delete',
      },
    },
    {
      key: 'roles',
      label: 'Role Management',
      actions: {
        read: 'roles.view',
        write: 'roles.edit',
        create: 'roles.create',
        delete: 'roles.delete',
      },
    },
  ].filter(row => Object.values(row.actions).some(permission => availablePermissions.has(permission)))
})

const availablePermissionValues = computed(() => permissionMatrix.value.flatMap(row => Object.values(row.actions)))
const selectedPermissionCount = computed(() => availablePermissionValues.value.filter(permission => form.permissions.includes(permission)).length)
const isSelectAll = computed({
  get: () => availablePermissionValues.value.length > 0 && selectedPermissionCount.value === availablePermissionValues.value.length,
  set: value => {
    form.permissions = value ? [...availablePermissionValues.value] : []
  },
})
const isIndeterminate = computed(() => selectedPermissionCount.value > 0 && selectedPermissionCount.value < availablePermissionValues.value.length)

watch(
  () => [props.isDialogVisible, props.role],
  () => {
    if (!props.isDialogVisible)
      return

    form.name = props.role?.name ?? ''
    form.permissions = [...(props.role?.permissions ?? [])]
  },
  { immediate: true },
)

const closeDialog = () => {
  emit('update:isDialogVisible', false)
}

const submit = () => {
  emit('submit', {
    id: props.role?.id ?? null,
    name: form.name,
    permissions: form.permissions,
  })
}
</script>

<template>
  <VDialog
    :width="$vuetify.display.smAndDown ? 'auto' : 900"
    :model-value="props.isDialogVisible"
    @update:model-value="closeDialog"
  >
    <VCard class="pa-sm-11 pa-3">
      <DialogCloseBtn
        variant="text"
        size="default"
        @click="closeDialog"
      />

      <VCardText>
        <div class="text-center mb-10">
          <h4 class="text-h4 mb-2">
            {{ props.role?.id ? 'Edit' : 'Add' }} Role
          </h4>

          <p class="text-body-1">
            {{ props.role?.id ? 'Edit Role' : 'Add Role' }}
          </p>
        </div>

        <VForm @submit.prevent="submit">
          <VTextField
            v-model="form.name"
            label="Role Name"
            placeholder="Enter Role Name"
            class="mb-6"
            :error-messages="props.errors.name ?? []"
          />

          <VAlert
            v-if="props.errors.permissions?.[0]"
            type="error"
            variant="tonal"
            class="mb-4"
          >
            {{ props.errors.permissions[0] }}
          </VAlert>

          <h5 class="text-h5 mb-4">
            Role Permissions
          </h5>

          <VTable class="text-no-wrap">
            <tbody>
              <tr>
                <td class="text-h6">
                  Administrator Access
                </td>
                <td
                  :colspan="permissionActions.length"
                  class="text-end"
                >
                  <VCheckbox
                    v-model="isSelectAll"
                    :indeterminate="isIndeterminate"
                    label="Select All"
                    density="comfortable"
                  />
                </td>
              </tr>

              <tr
                v-for="row in permissionMatrix"
                :key="row.key"
              >
                <td class="text-h6">
                  {{ row.label }}
                </td>

                <td
                  v-for="action in permissionActions"
                  :key="`${row.key}-${action.key}`"
                  class="text-end"
                >
                  <VCheckbox
                    v-if="row.actions[action.key]"
                    v-model="form.permissions"
                    :label="action.label"
                    :value="row.actions[action.key]"
                    density="comfortable"
                  />
                </td>
              </tr>
            </tbody>
          </VTable>

          <div class="d-flex align-center justify-center gap-3 mt-6">
            <VBtn
              type="submit"
              :loading="props.loading"
              :disabled="props.loading"
            >
              Submit
            </VBtn>

            <VBtn
              color="secondary"
              variant="outlined"
              :disabled="props.loading"
              @click="closeDialog"
            >
              Cancel
            </VBtn>
          </div>
        </VForm>
      </VCardText>
    </VCard>
  </VDialog>
</template>
