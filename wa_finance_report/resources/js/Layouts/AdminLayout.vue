<script setup>
import BaseDashboardLayout from './BaseDashboardLayout.vue';

const menuItems = [
    { title: 'Overview Platform', icon: 'ri-dashboard-line', route: 'admin.dashboard' },
    { title: 'Pengguna', icon: 'ri-user-settings-line', disabled: true },
    { title: 'Tenant Accounts', icon: 'ri-building-line', disabled: true },
    { title: 'Paket Langganan', icon: 'ri-price-tag-3-line', route: 'admin.subscription-plans.index' },
    { title: 'Subscriptions', icon: 'ri-bank-card-line', route: 'admin.subscriptions.index' },
    { title: 'Activity Logs', icon: 'ri-file-list-3-line', disabled: true },
    { title: 'System Settings', icon: 'ri-settings-3-line', disabled: true },
    { title: 'Profil Saya', icon: 'ri-user-line', route: 'profile.edit' },
];
</script>

<template>
    <BaseDashboardLayout section-label="Super Admin" :menu-items="menuItems"><slot /></BaseDashboardLayout>
</template>
