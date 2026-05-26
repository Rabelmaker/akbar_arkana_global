<script setup>
import CrmCongratulationsNorris from '@/views/dashboards/CrmCongratulationsNorris.vue'
import CrmExternalLinks from '@/views/dashboards/CrmExternalLinks.vue'
import CrmGrowthDonutCharts from '@/views/dashboards/CrmGrowthDonutCharts.vue'
import CrmMeetingSchedule from '@/views/dashboards/CrmMeetingSchedule.vue'
import CrmMonthlyBudget from '@/views/dashboards/CrmMonthlyBudget.vue'
import CrmMostSalesInCountries from '@/views/dashboards/CrmMostSalesInCountries.vue'
import CrmOrganicSessions from '@/views/dashboards/CrmOrganicSessions.vue'
import CrmPaymentHistory from '@/views/dashboards/CrmPaymentHistory.vue'
import CrmProfitBarWithGapCharts from '@/views/dashboards/CrmProfitBarWithGapCharts.vue'
import CrmProjectTimeline from '@/views/dashboards/CrmProjectTimeline.vue'
import CrmSocialNetworkVisits from '@/views/dashboards/CrmSocialNetworkVisits.vue'
import CrmUserList from '@/views/dashboards/CrmUserList.vue'
import CrmWeeklyOverview from '@/views/dashboards/CrmWeeklyOverview.vue'

const statisticsVertical = [
  {
    title: 'Total Orders',
    color: 'primary',
    icon: 'ri-shopping-cart-line',
    stats: '155k',
    change: 22,
    subtitle: 'Last 4 months',
  },
  {
    title: 'Total Sales',
    color: 'success',
    icon: 'ri-handbag-line',
    stats: '13.4k',
    change: 38,
    subtitle: 'Last Six months',
  },
]
</script>

<template>
  <VRow class="match-height">
    <VCol
      cols="12"
      md="4"
    >
      <CrmCongratulationsNorris />
    </VCol>

    <VCol
      v-for="statistics in statisticsVertical"
      :key="statistics.title"
      cols="12"
      sm="3"
      md="2"
    >
      <CardStatisticsVertical v-bind="statistics" />
    </VCol>

    <VCol
      cols="12"
      sm="3"
      md="2"
    >
      <CrmProfitBarWithGapCharts />
    </VCol>

    <VCol
      cols="12"
      md="2"
      sm="3"
    >
      <CrmGrowthDonutCharts />
    </VCol>

    <VCol
      cols="12"
      md="4"
    >
      <CrmOrganicSessions />
    </VCol>

    <VCol
      cols="12"
      md="8"
    >
      <CrmProjectTimeline />
    </VCol>

    <VCol
      cols="12"
      sm="6"
      md="4"
    >
      <CrmWeeklyOverview />
    </VCol>

    <VCol
      cols="12"
      sm="6"
      md="4"
    >
      <CrmSocialNetworkVisits />
    </VCol>

    <VCol
      cols="12"
      md="4"
      sm="6"
    >
      <CrmMonthlyBudget />
    </VCol>

    <VCol
      cols="12"
      md="4"
      sm="6"
    >
      <CrmMeetingSchedule />
    </VCol>

    <VCol
      cols="12"
      sm="6"
      md="4"
    >
      <CrmExternalLinks />
    </VCol>

    <VCol
      cols="12"
      sm="6"
      md="4"
    >
      <CrmPaymentHistory />
    </VCol>

    <VCol
      cols="12"
      md="4"
    >
      <CrmMostSalesInCountries />
    </VCol>

    <VCol
      cols="12"
      md="8"
    >
      <CrmUserList />
    </VCol>
  </VRow>
</template>

<style lang="scss">
@use "@core-scss/template/libs/apex-chart.scss";
</style>
