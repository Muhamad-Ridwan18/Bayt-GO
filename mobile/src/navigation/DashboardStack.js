import React from 'react';
import { createNativeStackNavigator } from '@react-navigation/native-stack';
import { useAuth } from '../context/AuthContext';
import HomeScreen from '../screens/HomeScreen';
import MuthowifDashboardScreen from '../screens/MuthowifDashboardScreen';
import MuthowifPendingScreen from '../screens/MuthowifPendingScreen';
import DirectoryScreen from '../screens/DirectoryScreen';
import MuthowifDetailScreen from '../screens/MuthowifDetailScreen';
import BookingFormScreen from '../screens/BookingFormScreen';
import BookingPaymentScreen from '../screens/BookingPaymentScreen';
import ScheduleScreen from '../screens/ScheduleScreen';
import ServicesScreen from '../screens/ServicesScreen';
import PortfolioScreen from '../screens/PortfolioScreen';
import PortfolioEditScreen from '../screens/PortfolioEditScreen';
import EmergencyOffersScreen from '../screens/EmergencyOffersScreen';
import SupportPackagesScreen from '../screens/SupportPackagesScreen';
import SupportCatalogScreen from '../screens/SupportCatalogScreen';
import SupportPackageDetailScreen from '../screens/SupportPackageDetailScreen';
import SupportPackageBookScreen from '../screens/SupportPackageBookScreen';
import AffiliateScreen from '../screens/AffiliateScreen';
import EditMuthowifProfileScreen from '../screens/EditMuthowifProfileScreen';
import CampaignDetailScreen from '../screens/CampaignDetailScreen';
import ArticleDetailScreen from '../screens/ArticleDetailScreen';
import ArticlesListScreen from '../screens/ArticlesListScreen';
import TermsScreen from '../screens/TermsScreen';
import { SupportCreateGate, SupportDetailGate, SupportListGate } from './SupportStack';

const Stack = createNativeStackNavigator();

const stackScreenOptions = {
  headerShown: false,
  animation: 'slide_from_right',
  gestureEnabled: true,
  fullScreenGestureEnabled: true,
  gestureDirection: 'horizontal',
};

function DashboardRoot(props) {
  const { isAuthenticated, isMuthowif, isVerifiedMuthowif, isPendingMuthowif } = useAuth();

  if (!isAuthenticated) {
    return <HomeScreen {...props} />;
  }
  if (isPendingMuthowif) {
    return <MuthowifPendingScreen {...props} />;
  }
  if (isMuthowif && isVerifiedMuthowif) {
    return <MuthowifDashboardScreen {...props} />;
  }
  if (isMuthowif) {
    return <MuthowifPendingScreen {...props} />;
  }
  return <HomeScreen {...props} />;
}

export default function DashboardStack() {
  return (
    <Stack.Navigator screenOptions={stackScreenOptions}>
      <Stack.Screen name="DashboardMain" component={DashboardRoot} />
      <Stack.Screen name="Directory" component={DirectoryScreen} />
      <Stack.Screen name="MuthowifDetail" component={MuthowifDetailScreen} />
      <Stack.Screen name="BookingForm" component={BookingFormScreen} />
      <Stack.Screen name="BookingPayment" component={BookingPaymentScreen} />
      <Stack.Screen name="Schedule" component={ScheduleScreen} />
      <Stack.Screen name="Services" component={ServicesScreen} />
      <Stack.Screen name="Portfolio" component={PortfolioScreen} />
      <Stack.Screen name="PortfolioEdit" component={PortfolioEditScreen} />
      <Stack.Screen name="SupportPackages" component={SupportPackagesScreen} />
      <Stack.Screen name="SupportCatalog" component={SupportCatalogScreen} />
      <Stack.Screen name="SupportPackageDetail" component={SupportPackageDetailScreen} />
      <Stack.Screen name="SupportPackageBook" component={SupportPackageBookScreen} />
      <Stack.Screen name="Affiliate" component={AffiliateScreen} />
      <Stack.Screen name="EmergencyOffers" component={EmergencyOffersScreen} />
      <Stack.Screen name="EditMuthowifProfile" component={EditMuthowifProfileScreen} />
      <Stack.Screen name="CampaignDetail" component={CampaignDetailScreen} />
      <Stack.Screen name="ArticleDetail" component={ArticleDetailScreen} />
      <Stack.Screen name="ArticlesList" component={ArticlesListScreen} />
      <Stack.Screen name="Terms" component={TermsScreen} />
      <Stack.Screen name="SupportList" component={SupportListGate} />
      <Stack.Screen name="SupportCreate" component={SupportCreateGate} />
      <Stack.Screen name="SupportDetail" component={SupportDetailGate} />
    </Stack.Navigator>
  );
}
