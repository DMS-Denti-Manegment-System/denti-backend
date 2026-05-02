// src/modules/alerts/Pages/AlertsPage.tsx

import React from 'react'
import { AlertList } from '../Components/AlertList'

interface AlertsPageProps {
  currentUser?: string
}

export const AlertsPage: React.FC<AlertsPageProps> = () => {
  return (
    <AlertList 
      showDashboard={true}
    />
  )
}