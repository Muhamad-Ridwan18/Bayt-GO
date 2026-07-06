import React, { useCallback, useState } from 'react';
import {
  View,
  Text,
  StyleSheet,
  ScrollView,
  TouchableOpacity,
  ActivityIndicator,
  RefreshControl,
  Image,
} from 'react-native';
import { useFocusEffect } from '@react-navigation/native';
import { LinearGradient } from 'expo-linear-gradient';
import { Ionicons } from '@expo/vector-icons';
import TabPageHeader from '../components/TabPageHeader';
import { useAuth } from '../context/AuthContext';
import { fetchProfile } from '../api/profile';
import { fetchCustomerDashboard, fetchMuthowifDashboard } from '../api/dashboard';
import { resetRoot } from '../navigation/rootNavigation';
import { colors } from '../theme/colors';
import { resolveMediaUrl } from '../utils/mediaUrl';

const STAT_ICONS = {
  'Booking Aktif': 'calendar-outline',
  'Tiket Bantuan': 'help-buoy-outline',
  'Perjalanan Mendatang': 'airplane-outline',
  'Ulasan Diberikan': 'star-outline',
  'Permintaan Baru': 'mail-unread-outline',
  'Pendapatan Bulan Ini': 'wallet-outline',
  'Rating': 'star-outline',
};

function StatCard({ stat }) {
  const icon = STAT_ICONS[stat.label] || 'analytics-outline';
  const accent = stat.color || colors.baytgo;

  return (
    <View style={styles.statCard}>
      <View style={[styles.statIcon, { backgroundColor: `${accent}18` }]}>
        <Ionicons name={icon} size={18} color={accent} />
      </View>
      <Text style={styles.statValue}>{stat.value}</Text>
      <Text style={styles.statLabel} numberOfLines={2}>{stat.label}</Text>
    </View>
  );
}

function MenuRow({ icon, label, onPress, iconBg = colors.baytgoLight, danger = false, isLast = false }) {
  return (
    <TouchableOpacity
      style={[styles.menuRow, isLast && styles.menuRowLast]}
      onPress={onPress}
      activeOpacity={0.88}
    >
      <View style={[styles.menuIcon, { backgroundColor: danger ? '#FEE2E2' : iconBg }]}>
        <Ionicons name={icon} size={20} color={danger ? '#B91C1C' : colors.baytgo} />
      </View>
      <Text style={[styles.menuLabel, danger && styles.menuLabelDanger]}>{label}</Text>
      {!danger ? <Ionicons name="chevron-forward" size={18} color={colors.slate400} /> : null}
    </TouchableOpacity>
  );
}

function Section({ title, children }) {
  return (
    <View style={styles.section}>
      {title ? <Text style={styles.sectionTitle}>{title}</Text> : null}
      <View style={styles.sectionCard}>{children}</View>
    </View>
  );
}

export default function ProfileScreen({ navigation }) {
  const { user, token, logout, isMuthowif, isVerifiedMuthowif, updateLocalUser } = useAuth();

  const [loading, setLoading] = useState(true);
  const [refreshing, setRefreshing] = useState(false);
  const [profile, setProfile] = useState(null);
  const [stats, setStats] = useState([]);

  const load = useCallback(async (refresh = false) => {
    if (refresh) setRefreshing(true);
    else setLoading(true);

    try {
      const [profileData, dashboardData] = await Promise.all([
        fetchProfile(token),
        isMuthowif ? fetchMuthowifDashboard(token) : fetchCustomerDashboard(token),
      ]);
      setProfile(profileData);
      setStats(dashboardData.stats || []);
      if (profileData?.muthowif?.verification_status) {
        updateLocalUser({ muthowif_verification_status: profileData.muthowif.verification_status });
      }
    } catch {
      setProfile(null);
      setStats([]);
    } finally {
      setLoading(false);
      setRefreshing(false);
    }
  }, [token, isMuthowif, updateLocalUser]);

  useFocusEffect(
    useCallback(() => {
      load();
    }, [load]),
  );

  const handleLogout = async () => {
    await logout();
    resetRoot(navigation, [{ name: 'Main' }]);
  };

  const photoUri = resolveMediaUrl(profile?.muthowif?.photo_url);
  const roleLabel = isMuthowif
    ? (isVerifiedMuthowif ? 'Muthowif Terverifikasi' : 'Muthowif · Menunggu verifikasi')
    : 'Jamaah';
  const roleIcon = isVerifiedMuthowif ? 'shield-checkmark' : isMuthowif ? 'time-outline' : 'person-outline';
  const roleColor = isVerifiedMuthowif ? colors.emerald600 : isMuthowif ? '#D97706' : colors.baytgo;

  const customerMenus = [
    { icon: 'receipt-outline', label: 'Pesanan Saya', onPress: () => navigation.getParent()?.navigate('BookingsTab', { screen: 'BookingsList' }) },
    { icon: 'help-buoy-outline', label: 'Tiket Bantuan', onPress: () => navigation.getParent()?.navigate('SupportTab', { screen: 'SupportList' }) },
  ];

  const muthowifMenus = [
    { icon: 'clipboard-outline', label: 'Permintaan Booking', onPress: () => navigation.getParent()?.navigate('MuthowifBookingsTab', { screen: 'MuthowifBookingsList' }) },
    { icon: 'wallet-outline', label: 'Dompet', onPress: () => navigation.getParent()?.navigate('WalletTab', { screen: 'WalletMain' }) },
    { icon: 'calendar-outline', label: 'Jadwal Libur', onPress: () => navigation.getParent()?.navigate('HomeTab', { screen: 'Schedule' }) },
    { icon: 'pricetag-outline', label: 'Kelola Layanan', onPress: () => navigation.getParent()?.navigate('HomeTab', { screen: 'Services' }) },
    { icon: 'medkit-outline', label: 'Paket Layanan Pendukung', onPress: () => navigation.getParent()?.navigate('HomeTab', { screen: 'SupportPackages' }) },
    { icon: 'images-outline', label: 'Portfolio', onPress: () => navigation.getParent()?.navigate('HomeTab', { screen: 'Portfolio' }) },
    { icon: 'globe-outline', label: 'Profil Publik Muthowif', onPress: () => navigation.navigate('EditMuthowifProfile', { profile }) },
  ];

  return (
    <View style={styles.container}>
      <TabPageHeader title="Profil" />

      {loading && !refreshing ? (
        <ActivityIndicator color={colors.baytgo} style={styles.loader} />
      ) : (
        <ScrollView
          contentContainerStyle={styles.scroll}
          showsVerticalScrollIndicator={false}
          refreshControl={
            <RefreshControl refreshing={refreshing} onRefresh={() => load(true)} tintColor={colors.baytgo} />
          }
        >
          <View style={styles.profileCard}>
            <View style={styles.avatarRing}>
              {photoUri ? (
                <Image source={{ uri: photoUri }} style={styles.avatarImage} resizeMode="cover" />
              ) : (
                <LinearGradient colors={[colors.baytgo, colors.baytgoDark]} style={styles.avatarFallback}>
                  <Text style={styles.avatarText}>{user?.name?.charAt(0)?.toUpperCase() || 'U'}</Text>
                </LinearGradient>
              )}
            </View>

            <Text style={styles.name}>{user?.name}</Text>
            <Text style={styles.email}>{user?.email}</Text>

            <View style={[styles.roleBadge, { borderColor: `${roleColor}30`, backgroundColor: `${roleColor}12` }]}>
              <Ionicons name={roleIcon} size={13} color={roleColor} />
              <Text style={[styles.roleText, { color: roleColor }]}>{roleLabel}</Text>
            </View>

            {profile?.user?.phone || profile?.muthowif?.phone ? (
              <View style={styles.contactRow}>
                <Ionicons name="call-outline" size={14} color={colors.slate500} />
                <Text style={styles.contactText}>
                  {profile?.user?.phone || profile?.muthowif?.phone}
                </Text>
              </View>
            ) : null}

            {isMuthowif && profile?.muthowif?.work_location_label ? (
              <View style={styles.contactRow}>
                <Ionicons name="location-outline" size={14} color={colors.slate500} />
                <Text style={styles.contactText}>{profile.muthowif.work_location_label}</Text>
              </View>
            ) : null}

            <TouchableOpacity
              style={styles.editChip}
              onPress={() => navigation.navigate('EditProfile', { profile })}
              activeOpacity={0.88}
            >
              <Ionicons name="create-outline" size={14} color={colors.baytgo} />
              <Text style={styles.editChipText}>Edit Profil</Text>
            </TouchableOpacity>
          </View>

          {stats.length > 0 ? (
            <View style={styles.statsGrid}>
              {stats.map((stat) => (
                <StatCard key={stat.label} stat={stat} />
              ))}
            </View>
          ) : null}

          {!isMuthowif ? (
            <Section title="Aktivitas">
              {customerMenus.map((item, index) => (
                <MenuRow key={item.label} {...item} isLast={index === customerMenus.length - 1} />
              ))}
            </Section>
          ) : isVerifiedMuthowif ? (
            <Section title="Kelola Muthowif">
              {muthowifMenus.map((item, index) => (
                <MenuRow key={item.label} {...item} isLast={index === muthowifMenus.length - 1} />
              ))}
            </Section>
          ) : null}

          <Section title="Keamanan">
            <MenuRow
              icon="key-outline"
              label="Ganti Password"
              onPress={() => navigation.navigate('ChangePassword')}
              isLast
            />
          </Section>

          <View style={styles.section}>
            <View style={styles.sectionCard}>
              <MenuRow icon="log-out-outline" label="Keluar" onPress={handleLogout} danger isLast />
            </View>
          </View>
        </ScrollView>
      )}
    </View>
  );
}

const styles = StyleSheet.create({
  container: { flex: 1, backgroundColor: colors.canvas },
  loader: { marginTop: 40 },
  scroll: { paddingHorizontal: 20, paddingTop: 16, paddingBottom: 32 },
  profileCard: {
    backgroundColor: colors.white,
    borderRadius: 22,
    padding: 20,
    alignItems: 'center',
    borderWidth: 1,
    borderColor: 'rgba(26,61,52,0.08)',
    shadowColor: '#0F2E28',
    shadowOffset: { width: 0, height: 8 },
    shadowOpacity: 0.1,
    shadowRadius: 20,
    elevation: 8,
    marginBottom: 16,
  },
  avatarRing: {
    padding: 4,
    borderRadius: 52,
    borderWidth: 3,
    borderColor: colors.goldLight,
    backgroundColor: colors.white,
    marginBottom: 12,
  },
  avatarImage: { width: 88, height: 88, borderRadius: 44 },
  avatarFallback: {
    width: 88,
    height: 88,
    borderRadius: 44,
    alignItems: 'center',
    justifyContent: 'center',
  },
  avatarText: { fontSize: 32, fontWeight: '900', color: colors.gold },
  name: { fontSize: 20, fontWeight: '900', color: colors.slate900, textAlign: 'center' },
  email: { marginTop: 4, fontSize: 13, fontWeight: '600', color: colors.slate500 },
  roleBadge: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 6,
    marginTop: 12,
    paddingHorizontal: 12,
    paddingVertical: 6,
    borderRadius: 999,
    borderWidth: 1,
  },
  roleText: { fontSize: 11, fontWeight: '800' },
  contactRow: { flexDirection: 'row', alignItems: 'center', gap: 6, marginTop: 8 },
  contactText: { fontSize: 13, fontWeight: '600', color: colors.slate600 },
  editChip: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 6,
    marginTop: 14,
    paddingHorizontal: 14,
    paddingVertical: 8,
    borderRadius: 999,
    backgroundColor: colors.baytgoLight,
    borderWidth: 1,
    borderColor: 'rgba(26,61,52,0.1)',
  },
  editChipText: { fontSize: 12, fontWeight: '800', color: colors.baytgo },
  statsGrid: {
    flexDirection: 'row',
    flexWrap: 'wrap',
    gap: 10,
    marginBottom: 16,
  },
  statCard: {
    width: '48%',
    flexGrow: 1,
    flexBasis: '45%',
    backgroundColor: colors.white,
    borderRadius: 16,
    padding: 14,
    borderWidth: 1,
    borderColor: 'rgba(26,61,52,0.08)',
  },
  statIcon: {
    width: 36,
    height: 36,
    borderRadius: 10,
    alignItems: 'center',
    justifyContent: 'center',
    marginBottom: 10,
  },
  statValue: { fontSize: 18, fontWeight: '900', color: colors.slate900 },
  statLabel: { marginTop: 2, fontSize: 11, fontWeight: '600', color: colors.slate500, lineHeight: 15 },
  section: { marginBottom: 16 },
  sectionTitle: {
    fontSize: 12,
    fontWeight: '800',
    color: colors.slate500,
    textTransform: 'uppercase',
    letterSpacing: 0.5,
    marginBottom: 8,
    marginLeft: 4,
  },
  sectionCard: {
    backgroundColor: colors.white,
    borderRadius: 18,
    borderWidth: 1,
    borderColor: 'rgba(26,61,52,0.08)',
    overflow: 'hidden',
  },
  menuRow: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 12,
    paddingHorizontal: 14,
    paddingVertical: 14,
    borderBottomWidth: 1,
    borderBottomColor: colors.slate100,
  },
  menuIcon: {
    width: 40,
    height: 40,
    borderRadius: 12,
    alignItems: 'center',
    justifyContent: 'center',
  },
  menuLabel: { flex: 1, fontSize: 14, fontWeight: '700', color: colors.slate800 },
  menuLabelDanger: { color: '#B91C1C' },
  menuRowLast: { borderBottomWidth: 0 },
});
