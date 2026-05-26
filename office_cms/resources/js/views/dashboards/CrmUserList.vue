<script setup>
import avatar1 from '@images/avatars/avatar-1.png'
import avatar2 from '@images/avatars/avatar-2.png'
import avatar3 from '@images/avatars/avatar-3.png'
import avatar4 from '@images/avatars/avatar-4.png'
import avatar5 from '@images/avatars/avatar-5.png'
import avatar6 from '@images/avatars/avatar-6.png'

const headers = [
  {
    title: 'User',
    key: 'user',
    sortable: false,
  },
  {
    title: 'Email',
    key: 'email',
    sortable: false,
  },
  {
    title: 'Role',
    key: 'role',
    sortable: false,
  },
  {
    title: 'Status',
    key: 'status',
    sortable: false,
  },
]

const users = [
  {
    id: 1,
    fullName: 'Alina Hartono',
    username: 'alina.h',
    email: 'alina.h@officecms.test',
    role: 'Admin',
    status: 'Active',
    avatar: avatar1,
  },
  {
    id: 2,
    fullName: 'Bagus Pratama',
    username: 'bagus.p',
    email: 'bagus.p@officecms.test',
    role: 'Editor',
    status: 'Pending',
    avatar: avatar2,
  },
  {
    id: 3,
    fullName: 'Cindy Laksmi',
    username: 'cindy.l',
    email: 'cindy.l@officecms.test',
    role: 'Maintainer',
    status: 'Active',
    avatar: avatar3,
  },
  {
    id: 4,
    fullName: 'Dimas Kurnia',
    username: 'dimas.k',
    email: 'dimas.k@officecms.test',
    role: 'Author',
    status: 'Inactive',
    avatar: avatar4,
  },
  {
    id: 5,
    fullName: 'Eva Maharani',
    username: 'eva.m',
    email: 'eva.m@officecms.test',
    role: 'Subscriber',
    status: 'Active',
    avatar: avatar5,
  },
  {
    id: 6,
    fullName: 'Farhan Yusuf',
    username: 'farhan.y',
    email: 'farhan.y@officecms.test',
    role: 'Editor',
    status: 'Pending',
    avatar: avatar6,
  },
]

const resolveUserRoleVariant = role => {
  const roleLowerCase = role.toLowerCase()
  if (roleLowerCase === 'subscriber')
    return { color: 'success', icon: 'ri-user-line' }
  if (roleLowerCase === 'author')
    return { color: 'error', icon: 'ri-computer-line' }
  if (roleLowerCase === 'maintainer')
    return { color: 'info', icon: 'ri-pie-chart-line' }
  if (roleLowerCase === 'editor')
    return { color: 'warning', icon: 'ri-edit-box-line' }
  if (roleLowerCase === 'admin')
    return { color: 'primary', icon: 'ri-vip-crown-line' }

  return { color: 'success', icon: 'ri-user-line' }
}

const resolveUserStatusVariant = stat => {
  const statLowerCase = stat.toLowerCase()
  if (statLowerCase === 'pending')
    return 'warning'
  if (statLowerCase === 'active')
    return 'success'
  if (statLowerCase === 'inactive')
    return 'secondary'

  return 'primary'
}
</script>

<template>
  <section>
    <VCard>
      <VDataTable
        :items="users"
        :headers="headers"
        class="text-no-wrap rounded-0"
      >
        <template #item.user="{ item }">
          <div class="d-flex align-center">
            <VAvatar
              size="34"
              :variant="!item.avatar ? 'tonal' : undefined"
              :color="!item.avatar ? resolveUserRoleVariant(item.role).color : undefined"
              class="me-3"
            >
              <VImg
                v-if="item.avatar"
                :src="item.avatar"
              />
              <span v-else>{{ avatarText(item.fullName) }}</span>
            </VAvatar>

            <div class="d-flex flex-column">
              <span class="text-link text-base font-weight-medium">
                {{ item.fullName }}
              </span>

              <span class="text-sm text-medium-emphasis">@{{ item.username }}</span>
            </div>
          </div>
        </template>

        <template #item.role="{ item }">
          <div class="d-flex gap-2">
            <VIcon
              :icon="resolveUserRoleVariant(item.role).icon"
              :color="resolveUserRoleVariant(item.role).color"
              size="22"
            />
            <span class="text-capitalize text-high-emphasis">{{ item.role }}</span>
          </div>
        </template>

        <template #item.status="{ item }">
          <VChip
            :color="resolveUserStatusVariant(item.status)"
            size="small"
            class="text-capitalize"
          >
            {{ item.status }}
          </VChip>
        </template>

        <template #bottom />
      </VDataTable>
    </VCard>
  </section>
</template>
