<script setup>
import BaseDashboardLayout from './BaseDashboardLayout.vue';
import { usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

const page = usePage();

const menuItems = computed(() => [
    { title: 'Dashboard', icon: 'ri-dashboard-line', route: 'dashboard' },
    { title: 'Transaksi', icon: 'ri-exchange-dollar-line', route: 'transactions.index' },
    { title: 'Rekening Keuangan', icon: 'ri-wallet-3-line', route: 'financial-accounts.index' },
    { title: 'Kategori', icon: 'ri-folder-chart-line', route: 'transaction-categories.index' },
    { title: 'Budget', icon: 'ri-funds-line', route: 'budgets.index' },
    { title: 'Laporan', icon: 'ri-bar-chart-box-line', route: 'reports.index' },
    { title: 'Integrations', icon: 'ri-plug-line', disabled: true },
    { title: 'API Tokens', icon: 'ri-key-2-line', route: 'api-tokens.index' },
    {
        title: 'Account Settings',
        icon: 'ri-settings-3-line',
        route: 'account.settings.edit',
        disabled: !page.props.auth.account?.can_manage,
    },
    { title: 'Profile', icon: 'ri-user-line', route: 'profile.edit' },
]);
</script>

<template>
    <BaseDashboardLayout section-label="User Dashboard" :menu-items="menuItems"><slot /></BaseDashboardLayout>
</template>
