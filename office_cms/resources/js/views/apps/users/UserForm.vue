<script setup>
import { PerfectScrollbar } from 'vue3-perfect-scrollbar'

const props = defineProps({
  isDrawerOpen: {
    type: Boolean,
    required: true,
  },
  user: {
    type: Object,
    default: null,
  },
  roles: {
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
  'update:isDrawerOpen',
  'submit',
])

const refForm = ref()
const isFormValid = ref(false)
const form = ref(defaultForm())

function defaultForm() {
  return {
    id: null,
    name: '',
    email: '',
    role: null,
    password: '',
    password_confirmation: '',
  }
}

const title = computed(() => props.user?.id ? 'Edit User' : 'Add User')
const submitLabel = computed(() => props.user?.id ? 'Update' : 'Submit')
const isEditing = computed(() => Boolean(props.user?.id))
const roleErrors = computed(() => props.errors.roles ?? props.errors.role ?? [])

const syncForm = () => {
  form.value = {
    id: props.user?.id ?? null,
    name: props.user?.name ?? '',
    email: props.user?.email ?? '',
    role: props.user?.role ?? null,
    password: '',
    password_confirmation: '',
  }
}

const closeDrawer = () => {
  emit('update:isDrawerOpen', false)
  nextTick(() => {
    refForm.value?.resetValidation()
    syncForm()
  })
}

const onSubmit = async () => {
  const result = await refForm.value?.validate()

  if (!result?.valid)
    return

  emit('submit', {
    id: form.value.id,
    name: form.value.name,
    email: form.value.email,
    role: form.value.role,
    password: form.value.password || undefined,
    password_confirmation: form.value.password_confirmation || undefined,
  })
}

watch(
  () => [props.user, props.isDrawerOpen],
  syncForm,
  { immediate: true },
)
</script>

<template>
  <VNavigationDrawer
    temporary
    :width="400"
    location="end"
    class="scrollable-content"
    :model-value="props.isDrawerOpen"
    @update:model-value="emit('update:isDrawerOpen', $event)"
  >
    <AppDrawerHeaderSection
      :title="title"
      @cancel="closeDrawer"
    />

    <VDivider />

    <PerfectScrollbar :options="{ wheelPropagation: false }">
      <VCard flat>
        <VCardText>
          <VAlert
            v-if="props.errors.email?.[0] && !props.errors.name && !props.errors.password && !props.errors.role"
            type="error"
            variant="tonal"
            class="mb-4"
          >
            {{ props.errors.email[0] }}
          </VAlert>

          <VForm
            ref="refForm"
            v-model="isFormValid"
            @submit.prevent="onSubmit"
          >
            <VRow>
              <VCol cols="12">
                <VTextField
                  v-model="form.name"
                  label="Full Name"
                  placeholder="John Doe"
                  :rules="[requiredValidator]"
                  :error-messages="props.errors.name ?? []"
                />
              </VCol>

              <VCol cols="12">
                <VTextField
                  v-model="form.email"
                  label="Email"
                  placeholder="johndoe@email.com"
                  :rules="[requiredValidator, emailValidator]"
                  :error-messages="props.errors.email ?? []"
                />
              </VCol>

              <VCol cols="12">
                <VSelect
                  v-model="form.role"
                  label="Select Role"
                  placeholder="Select Role"
                  :items="props.roles"
                  :rules="[requiredValidator]"
                  :error-messages="roleErrors"
                />
              </VCol>

              <VCol cols="12">
                <VTextField
                  v-model="form.password"
                  label="Password"
                  :placeholder="isEditing ? 'Leave blank to keep current password' : 'Enter password'"
                  :type="'password'"
                  :rules="isEditing ? [] : [requiredValidator]"
                  :error-messages="props.errors.password ?? []"
                />
              </VCol>

              <VCol cols="12">
                <VTextField
                  v-model="form.password_confirmation"
                  label="Confirm Password"
                  placeholder="Confirm password"
                  :type="'password'"
                  :rules="form.password ? [requiredValidator] : []"
                />
              </VCol>

              <VCol cols="12">
                <VBtn
                  type="submit"
                  class="me-4"
                  :loading="props.loading"
                >
                  {{ submitLabel }}
                </VBtn>

                <VBtn
                  type="button"
                  variant="outlined"
                  color="error"
                  @click="closeDrawer"
                >
                  Cancel
                </VBtn>
              </VCol>
            </VRow>
          </VForm>
        </VCardText>
      </VCard>
    </PerfectScrollbar>
  </VNavigationDrawer>
</template>
