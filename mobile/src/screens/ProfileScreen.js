import React, { useCallback, useState } from 'react';
import { ActivityIndicator, Linking, RefreshControl, ScrollView, StyleSheet, Text, View } from 'react-native';
import { useFocusEffect } from '@react-navigation/native';
import { LinearGradient } from 'expo-linear-gradient';
import {
  BarChart3,
  Briefcase,
  Calendar,
  ChevronRight,
  ClipboardList,
  Clock,
  FileText,
  Globe,
  Images,
  KeyRound,
  LifeBuoy,
  Link2,
  LogOut,
  Mail,
  MapPin,
  MessageCircle,
  Newspaper,
  Pencil,
  Phone,
  Plane,
  Receipt,
  ShieldCheck,
  Star,
  Tag,
  Trash2,
  User,
  Wallet,
  BellRing,
} from 'lucide-react-native';
import TabPageHeader from '../components/TabPageHeader';
import { useAuth } from '../context/AuthContext';
import { useBrand } from '../context/BrandContext';
import { fetchProfile, sendVerificationEmail, updateProfileLocale } from '../api/profile';
import { fetchCustomerDashboard, fetchMuthowifDashboard } from '../api/dashboard';
import { resetRoot } from '../navigation/rootNavigation';
import AppImage from '../ui/AppImage';
import Button from '../ui/Button';
import Card from '../ui/Card';
import PressableScale from '../ui/PressableScale';
import { SkeletonList } from '../ui/Skeleton';
import StatTile from '../ui/StatTile';
import { colors, gradients, layout, radius, spacing, typography } from '../theme/tokens';
import { resolveMediaUrl } from '../utils/mediaUrl';
import { notifyError, notifySuccess } from '../utils/feedback';
import LanguageSwitcher from '../components/LanguageSwitcher';
import { setStoredLocale } from '../utils/locale';
import { useLocale } from '../utils/locale';

const STAT_ICONS = {
  'Booking Aktif': Calendar,
  'Tiket Bantuan': LifeBuoy,
  'Perjalanan Mendatang': Plane,
  'Ulasan Diberikan': Star,
  'Permintaan Baru': Mail,
  'Pendapatan Bulan Ini': Wallet,
  Rating: Star,
};

function MenuRow({ icon: Icon, label, onPress, iconBg = colors.baytgoLight, danger = false, isLast = false }) {
  return (
    <PressableScale
      onPress={onPress}
      haptic="light"
      style={[styles.menuRow, isLast && styles.menuRowLast]}
    >
      <View style={[styles.menuIcon, { backgroundColor: danger ? colors.errorLight : iconBg }]}>
        <Icon size={20} color={danger ? colors.error : colors.baytgo} strokeWidth={2} />
      </View>
      <Text style={[styles.menuLabel, danger && styles.menuLabelDanger]}>{label}</Text>
      {!danger ? <ChevronRight size={18} color={colors.textMuted} strokeWidth={2} /> : null}
    </PressableScale>
  );
}

function Section({ title, children }) {
  return (
    <View style={styles.section}>
      {title ? <Text style={styles.sectionTitle}>{title}</Text> : null}
      <Card style={styles.sectionCard} padding={0} elevated={false} variant="flat">
        {children}
      </Card>
    </View>
  );
}

export default function ProfileScreen({ navigation }) {
  const { user, token, logout, isMuthowif, isVerifiedMuthowif, updateLocalUser } = useAuth();
  const { contactUrl } = useBrand();
  const locale = useLocale();
  const isEn = locale === 'en';

  const [loading, setLoading] = useState(true);
  const [refreshing, setRefreshing] = useState(false);
  const [profile, setProfile] = useState(null);
  const [stats, setStats] = useState([]);
  const [sendingVerification, setSendingVerification] = useState(false);

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
      if (profileData?.user?.locale) {
        setStoredLocale(profileData.user.locale).catch(() => {});
        updateLocalUser({ locale: profileData.user.locale });
      }
      if (profileData?.user || profileData?.muthowif?.verification_status) {
        updateLocalUser({
          ...(profileData?.user?.email_verified_at !== undefined
            ? { email_verified_at: profileData.user.email_verified_at }
            : {}),
          ...(profileData?.muthowif?.verification_status
            ? { muthowif_verification_status: profileData.muthowif.verification_status }
            : {}),
        });
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

  const handleResendVerification = async () => {
    setSendingVerification(true);
    try {
      const data = await sendVerificationEmail(token);
      notifySuccess(data.message || 'Tautan verifikasi baru telah dikirim ke email Anda.');
    } catch (err) {
      notifyError(err.message || 'Tidak dapat mengirim verifikasi');
    } finally {
      setSendingVerification(false);
    }
  };

  const photoUri = resolveMediaUrl(profile?.muthowif?.photo_url);
  const roleLabel = isMuthowif
    ? (isVerifiedMuthowif
      ? (isEn ? 'Verified Muthowif' : 'Muthowif Terverifikasi')
      : (isEn ? 'Muthowif · Pending verification' : 'Muthowif · Menunggu verifikasi'))
    : (isEn ? 'Pilgrim' : 'Jamaah');
  const RoleIcon = isVerifiedMuthowif ? ShieldCheck : isMuthowif ? Clock : User;
  const roleColor = isVerifiedMuthowif ? colors.success : isMuthowif ? colors.warning : colors.baytgo;

  const customerMenus = [
    { icon: Receipt, label: isEn ? 'My Orders' : 'Pesanan Saya', onPress: () => navigation.getParent()?.navigate('BookingsTab', { screen: 'BookingsList' }) },
    { icon: Link2, label: 'Affiliate', onPress: () => navigation.navigate('Affiliate') },
    { icon: Briefcase, label: isEn ? 'Support Services' : 'Layanan Pendukung', onPress: () => navigation.getParent()?.navigate('HomeTab', { screen: 'SupportCatalog' }) },
    { icon: LifeBuoy, label: isEn ? 'Support Tickets' : 'Tiket Bantuan', onPress: () => navigation.getParent()?.navigate('SupportTab', { screen: 'SupportList' }) },
  ];

  const muthowifMenus = [
    { icon: ClipboardList, label: isEn ? 'Booking Requests' : 'Permintaan Booking', onPress: () => navigation.getParent()?.navigate('MuthowifBookingsTab', { screen: 'MuthowifBookingsList' }) },
    { icon: Wallet, label: isEn ? 'Wallet' : 'Dompet', onPress: () => navigation.getParent()?.navigate('WalletTab', { screen: 'WalletMain' }) },
    { icon: Link2, label: 'Affiliate', onPress: () => navigation.getParent()?.navigate('HomeTab', { screen: 'Affiliate' }) },
    { icon: Calendar, label: isEn ? 'Time Off Schedule' : 'Jadwal Libur', onPress: () => navigation.getParent()?.navigate('HomeTab', { screen: 'Schedule' }) },
    { icon: Tag, label: isEn ? 'Manage Services' : 'Kelola Layanan', onPress: () => navigation.getParent()?.navigate('HomeTab', { screen: 'Services' }) },
    { icon: Briefcase, label: isEn ? 'Support Service Packages' : 'Paket Layanan Pendukung', onPress: () => navigation.getParent()?.navigate('HomeTab', { screen: 'SupportPackages' }) },
    { icon: Images, label: 'Portfolio', onPress: () => navigation.getParent()?.navigate('HomeTab', { screen: 'Portfolio' }) },
    { icon: Globe, label: isEn ? 'Public Muthowif Profile' : 'Profil Publik Muthowif', onPress: () => navigation.navigate('EditMuthowifProfile', { profile }) },
    { icon: LifeBuoy, label: isEn ? 'Support Tickets' : 'Tiket Bantuan', onPress: () => navigation.getParent()?.navigate('HomeTab', { screen: 'SupportList' }) },
  ];

  if (loading && !refreshing) {
    return (
      <View style={styles.container}>
        <TabPageHeader title={isEn ? 'Profile' : 'Profil'} />
        <SkeletonList count={3} style={styles.skeleton} />
      </View>
    );
  }

  return (
    <View style={styles.container}>
      <TabPageHeader title={isEn ? 'Profile' : 'Profil'} />

      <ScrollView
        contentContainerStyle={styles.scroll}
        showsVerticalScrollIndicator={false}
        refreshControl={
          <RefreshControl refreshing={refreshing} onRefresh={() => load(true)} tintColor={colors.baytgo} />
        }
      >
        <Card style={styles.profileCard} padding={spacing.xl} elevated>
          <View style={styles.avatarRing}>
            {photoUri ? (
              <AppImage uri={photoUri} size={88} rounded={radius.full} />
            ) : (
              <LinearGradient colors={gradients.primary} style={styles.avatarFallback}>
                <Text style={styles.avatarText}>{user?.name?.charAt(0)?.toUpperCase() || 'U'}</Text>
              </LinearGradient>
            )}
          </View>

          <Text style={styles.name}>{user?.name}</Text>
          <Text style={styles.email}>{user?.email}</Text>

          <View style={[styles.roleBadge, { borderColor: `${roleColor}30`, backgroundColor: `${roleColor}12` }]}>
            <RoleIcon size={13} color={roleColor} strokeWidth={2} />
            <Text style={[styles.roleText, { color: roleColor }]}>{roleLabel}</Text>
          </View>

          {profile?.user?.phone || profile?.muthowif?.phone ? (
            <View style={styles.contactRow}>
              <Phone size={14} color={colors.textSecondary} strokeWidth={2} />
              <Text style={styles.contactText}>
                {profile?.user?.phone || profile?.muthowif?.phone}
              </Text>
            </View>
          ) : null}

          {isMuthowif && profile?.muthowif?.work_location_label ? (
            <View style={styles.contactRow}>
              <MapPin size={14} color={colors.textSecondary} strokeWidth={2} />
              <Text style={styles.contactText}>{profile.muthowif.work_location_label}</Text>
            </View>
          ) : null}

          <View style={styles.editBtn}>
            <Button
              label="Edit Profil"
              onPress={() => navigation.navigate('EditProfile', { profile })}
              variant="secondary"
              size="sm"
              fullWidth={false}
              icon={<Pencil size={14} color={colors.baytgo} strokeWidth={2} />}
            />
          </View>
        </Card>

        {profile?.user && !profile.user.email_verified_at ? (
          <Card style={styles.verifyBox} padding={spacing.lg} elevated={false}>
            <Text style={styles.verifyText}>Email Anda belum diverifikasi.</Text>
            <PressableScale onPress={handleResendVerification} disabled={sendingVerification} haptic="light">
              {sendingVerification ? (
                <ActivityIndicator color={colors.baytgo} size="small" style={styles.verifySpinner} />
              ) : (
                <Text style={styles.verifyLink}>Klik di sini untuk mengirim ulang email verifikasi.</Text>
              )}
            </PressableScale>
          </Card>
        ) : null}

        {stats.length > 0 ? (
          <View style={styles.statsGrid}>
            {stats.map((stat) => {
              const Icon = STAT_ICONS[stat.label] || BarChart3;
              return (
                <View key={stat.label} style={styles.statItem}>
                  <StatTile
                    label={stat.label}
                    value={stat.value}
                    color={stat.color || colors.baytgo}
                    icon={Icon}
                  />
                </View>
              );
            })}
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
        ) : (
          <Section title="Kelola Muthowif">
            <MenuRow
              icon={Globe}
              label="Profil Publik Muthowif"
              onPress={() => navigation.navigate('EditMuthowifProfile', { profile })}
              isLast
            />
          </Section>
        )}

        <Section title="Keamanan">
          <MenuRow
            icon={KeyRound}
            label="Ganti Password"
            onPress={() => navigation.navigate('ChangePassword')}
          />
          {__DEV__ ? (
            <MenuRow
              icon={BellRing}
              label="Test Push Notification"
              onPress={() => navigation.navigate('PushNotificationTest')}
              iconBg="#FEF3C7"
            />
          ) : null}
          <MenuRow
            icon={Trash2}
            label="Hapus Akun"
            onPress={() => navigation.navigate('DeleteAccount')}
            danger
            isLast
          />
        </Section>

        <View style={styles.section}>
          <LanguageSwitcher
            onChange={async (locale) => {
              try {
                await updateProfileLocale(token, locale);
                updateLocalUser({ locale });
              } catch {
                // keep local locale even if sync fails
              }
            }}
          />
        </View>

        <Section title="Lainnya">
          <MenuRow
            icon={Newspaper}
            label="Artikel"
            onPress={() => navigation.getParent()?.navigate('HomeTab', { screen: 'ArticlesList' })}
          />
          {contactUrl ? (
            <MenuRow
              icon={MessageCircle}
              label="Hubungi kami"
              onPress={() => Linking.openURL(contactUrl)}
            />
          ) : null}
          <MenuRow
            icon={FileText}
            label="Syarat & Ketentuan"
            onPress={() => navigation.getParent()?.navigate('HomeTab', { screen: 'Terms' })}
            isLast
          />
        </Section>

        <View style={styles.section}>
          <Card style={styles.sectionCard} padding={0} elevated={false} variant="flat">
            <MenuRow icon={LogOut} label="Keluar" onPress={handleLogout} danger isLast />
          </Card>
        </View>
      </ScrollView>
    </View>
  );
}

const styles = StyleSheet.create({
  container: { flex: 1, backgroundColor: colors.background },
  skeleton: {
    paddingHorizontal: layout.screenPadding,
    paddingTop: spacing.lg,
  },
  scroll: {
    paddingHorizontal: layout.screenPadding,
    paddingTop: spacing.lg,
    paddingBottom: spacing.lg,
  },
  profileCard: {
    alignItems: 'center',
    marginBottom: spacing.lg,
    borderRadius: radius.md,
  },
  avatarRing: {
    padding: spacing.xs,
    borderRadius: 52,
    borderWidth: 3,
    borderColor: colors.goldLight,
    backgroundColor: colors.white,
    marginBottom: spacing.md,
  },
  avatarFallback: {
    width: 88,
    height: 88,
    borderRadius: 44,
    alignItems: 'center',
    justifyContent: 'center',
  },
  avatarText: {
    ...typography.title,
    color: colors.gold,
  },
  name: {
    ...typography.subtitle,
    fontFamily: 'PlusJakartaSans_800ExtraBold',
    color: colors.textPrimary,
    textAlign: 'center',
  },
  email: {
    marginTop: spacing.xs,
    ...typography.caption,
    color: colors.textSecondary,
  },
  roleBadge: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: spacing.sm,
    marginTop: spacing.md,
    paddingHorizontal: spacing.md,
    paddingVertical: spacing.sm,
    borderRadius: radius.full,
    borderWidth: 1,
  },
  roleText: {
    ...typography.label,
  },
  contactRow: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: spacing.sm,
    marginTop: spacing.sm,
  },
  contactText: {
    ...typography.caption,
    color: colors.slate600,
  },
  editBtn: { marginTop: spacing.lg },
  verifyBox: {
    marginBottom: spacing.lg,
    backgroundColor: colors.warningLight,
    borderColor: '#FDE68A',
  },
  verifyText: { ...typography.caption, color: '#92400E', fontWeight: '600' },
  verifyLink: {
    marginTop: spacing.sm,
    ...typography.caption,
    color: colors.baytgo,
    fontFamily: 'PlusJakartaSans_800ExtraBold',
  },
  verifySpinner: { marginTop: spacing.sm, alignSelf: 'flex-start' },
  statsGrid: {
    flexDirection: 'row',
    flexWrap: 'wrap',
    gap: spacing.md,
    marginBottom: spacing.lg,
  },
  statItem: {
    width: '48%',
    flexGrow: 1,
  },
  section: { marginBottom: spacing.lg },
  sectionTitle: {
    ...typography.label,
    color: colors.textSecondary,
    textTransform: 'uppercase',
    marginBottom: spacing.sm,
    marginLeft: spacing.xs,
  },
  sectionCard: {
    borderRadius: radius.md,
    overflow: 'hidden',
  },
  menuRow: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: spacing.md,
    paddingHorizontal: spacing.lg,
    paddingVertical: spacing.lg,
    borderBottomWidth: 1,
    borderBottomColor: colors.border,
  },
  menuIcon: {
    width: 40,
    height: 40,
    borderRadius: radius.sm,
    alignItems: 'center',
    justifyContent: 'center',
  },
  menuLabel: {
    flex: 1,
    ...typography.caption,
    fontFamily: 'PlusJakartaSans_700Bold',
    color: colors.slate800,
  },
  menuLabelDanger: { color: colors.error },
  menuRowLast: { borderBottomWidth: 0 },
});
