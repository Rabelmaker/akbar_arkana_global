<script setup>
import UserLayout from '@/Layouts/UserLayout.vue';
import { Head, router, useForm, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

const props = defineProps({
    account: { type: Object, required: true },
    accounts: { type: Array, required: true },
});

const page = usePage();
const selectedAccountId = ref(props.account.id);
const switching = ref(false);

const form = useForm({
    name: props.account.name,
    email: props.account.email ?? '',
    phone: props.account.phone ?? '',
    timezone: props.account.timezone,
    currency: props.account.currency,
    locale: props.account.locale,
});

const accountItems = computed(() => props.accounts.map((account) => ({
    title: `${account.name} · ${account.role}`,
    value: account.id,
    props: { disabled: account.status !== 'active' },
})));

const switchAccount = (accountId) => {
    if (!accountId || accountId === props.account.id || switching.value) return;

    switching.value = true;
    router.put(route('account.active.update', accountId), {}, {
        preserveScroll: true,
        onError: () => {
            selectedAccountId.value = props.account.id;
        },
        onFinish: () => {
            switching.value = false;
        },
    });
};

const submit = () => {
    form.patch(route('account.settings.update'), {
        preserveScroll: true,
    });
};
</script>

<template>
    <Head title="Account Settings" />

    <UserLayout>
        <div class="d-flex flex-wrap align-center justify-space-between gap-3 mb-6">
            <div>
                <h1 class="text-h4 mb-1">Account Settings</h1>
                <p class="text-medium-emphasis mb-0">Kelola identitas dan preferensi account aktif.</p>
            </div>
            <VChip color="success" variant="tonal" prepend-icon="ri-shield-check-line">
                {{ account.status }}
            </VChip>
        </div>

        <VAlert
            v-if="page.props.flash.success"
            type="success"
            variant="tonal"
            closable
            class="mb-6"
        >
            {{ page.props.flash.success }}
        </VAlert>

        <VRow>
            <VCol cols="12" lg="4">
                <VCard>
                    <VCardItem>
                        <VCardTitle>Account Aktif</VCardTitle>
                        <VCardSubtitle>Pilih workspace yang ingin dikelola.</VCardSubtitle>
                    </VCardItem>
                    <VCardText>
                        <VSelect
                            v-model="selectedAccountId"
                            :items="accountItems"
                            label="Pilih account"
                            variant="outlined"
                            :loading="switching"
                            :disabled="switching || accounts.length < 2"
                            @update:model-value="switchAccount"
                        />

                        <VList density="compact" class="pa-0">
                            <VListItem prepend-icon="ri-building-4-line" title="Nama" :subtitle="account.name" />
                            <VListItem prepend-icon="ri-money-dollar-circle-line" title="Mata uang" :subtitle="account.currency" />
                            <VListItem prepend-icon="ri-time-line" title="Zona waktu" :subtitle="account.timezone" />
                        </VList>
                    </VCardText>
                </VCard>
            </VCol>

            <VCol cols="12" lg="8">
                <VCard>
                    <VCardItem>
                        <VCardTitle>Profil Account</VCardTitle>
                        <VCardSubtitle>Informasi ini akan digunakan pada laporan dan dokumen finansial.</VCardSubtitle>
                    </VCardItem>

                    <VCardText>
                        <VForm @submit.prevent="submit">
                            <VRow>
                                <VCol cols="12">
                                    <VTextField
                                        v-model="form.name"
                                        label="Nama account"
                                        variant="outlined"
                                        :error-messages="form.errors.name"
                                    />
                                </VCol>
                                <VCol cols="12" md="6">
                                    <VTextField
                                        v-model="form.email"
                                        label="Email bisnis"
                                        type="email"
                                        variant="outlined"
                                        :error-messages="form.errors.email"
                                    />
                                </VCol>
                                <VCol cols="12" md="6">
                                    <VTextField
                                        v-model="form.phone"
                                        label="Nomor telepon"
                                        variant="outlined"
                                        :error-messages="form.errors.phone"
                                    />
                                </VCol>
                                <VCol cols="12" md="6">
                                    <VSelect
                                        v-model="form.timezone"
                                        label="Zona waktu"
                                        variant="outlined"
                                        :items="['Asia/Jakarta', 'Asia/Makassar', 'Asia/Jayapura']"
                                        :error-messages="form.errors.timezone"
                                    />
                                </VCol>
                                <VCol cols="12" md="3">
                                    <VSelect
                                        v-model="form.currency"
                                        label="Mata uang"
                                        variant="outlined"
                                        :items="['IDR']"
                                        :error-messages="form.errors.currency"
                                    />
                                </VCol>
                                <VCol cols="12" md="3">
                                    <VSelect
                                        v-model="form.locale"
                                        label="Bahasa"
                                        variant="outlined"
                                        :items="[
                                            { title: 'Indonesia', value: 'id' },
                                            { title: 'English', value: 'en' },
                                        ]"
                                        :error-messages="form.errors.locale"
                                    />
                                </VCol>
                            </VRow>

                            <div class="d-flex justify-end mt-2">
                                <VBtn
                                    type="submit"
                                    color="primary"
                                    prepend-icon="ri-save-line"
                                    :loading="form.processing"
                                    :disabled="form.processing || !form.isDirty"
                                >
                                    Simpan Perubahan
                                </VBtn>
                            </div>
                        </VForm>
                    </VCardText>
                </VCard>
            </VCol>
        </VRow>
    </UserLayout>
</template>
