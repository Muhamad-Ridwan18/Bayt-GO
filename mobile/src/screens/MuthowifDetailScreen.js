import React, { useCallback, useEffect, useMemo, useState } from 'react';
import {
  View, Text, StyleSheet, ScrollView, Alert,
} from 'react-native';
import { FlashList } from '@shopify/flash-list';
import { LinearGradient } from 'expo-linear-gradient';
import { SafeAreaView } from 'react-native-safe-area-context';
import {
  AlertCircle, Briefcase, Calendar, ChevronDown, ChevronLeft, ChevronUp,
  CirclePlus, Headphones, Images, Lock, MapPin, ShieldCheck, Star, User,
} from 'lucide-react-native';
import { fetchMuthowifDetail } from '../api/directory';
import { useAuth } from '../context/AuthContext';
import { navigateRoot } from '../navigation/rootNavigation';
import { AppImage, Button, Card, EmptyState, ErrorState, PressableScale, SkeletonList, StickyFooter } from '../ui';
import {
  AddOnListItem, PackageCard, PortfolioLightbox, ReviewItem, SectionCard,
  Stars, StatCell, styles as partStyles,
} from '../features/muthowif/MuthowifDetailParts';
import { colors, gradients, layout, radius, spacing, typography } from '../theme/tokens';
import { formatIdr } from '../utils/format';
import { resolveMediaUrl } from '../utils/mediaUrl';
import { useHideTabBarOnFocus } from '../hooks/useHideTabBarOnFocus';
import { useSafeAreaInsets } from 'react-native-safe-area-context';

export default function MuthowifDetailScreen({ navigation, route }) {
  const { token, isAuthenticated, user } = useAuth();
  const { id, startDate, endDate } = route.params;

  const [loading, setLoading] = useState(true);
  const [error, setError] = useState(null);
  const [profile, setProfile] = useState(null);
  const [services, setServices] = useState([]);
  const [addOns, setAddOns] = useState([]);
  const [portfolios, setPortfolios] = useState([]);
  const [portfoliosCount, setPortfoliosCount] = useState(0);
  const [reviews, setReviews] = useState([]);
  const [blockedDates, setBlockedDates] = useState([]);
  const [bookingIntent, setBookingIntent] = useState(null);
  const [lightbox, setLightbox] = useState({ visible: false, images: [], index: 0, title: '' });
  const [showBlocked, setShowBlocked] = useState(false);

  const allAddOns = useMemo(() => {
    if (addOns.length > 0) return addOns;
    const map = new Map();
    services.forEach((service) => {
      (service.add_ons || []).forEach((addon) => map.set(addon.id, addon));
    });
    return Array.from(map.values());
  }, [addOns, services]);

  const loadDetail = useCallback(async () => {
    setLoading(true);
    try {
      const data = await fetchMuthowifDetail({ token, id, startDate, endDate });
      setProfile(data.profile);
      setServices(data.services || []);
      setAddOns(data.add_ons || []);
      setPortfolios(data.portfolios || []);
      setPortfoliosCount(data.portfolios_count || 0);
      setReviews(data.reviews || []);
      setBlockedDates(data.blocked_dates || []);
      setBookingIntent(data.bookingIntent || null);
      setError(null);
    } catch (err) {
      setError(err.message || 'Gagal memuat profil');
    } finally {
      setLoading(false);
    }
  }, [token, id, startDate, endDate]);

  useEffect(() => { loadDetail(); }, [loadDetail]);
  useHideTabBarOnFocus(navigation);
  const insets = useSafeAreaInsets();
  const scrollBottomInset = 96 + Math.max(insets.bottom, spacing.md);

  const handleBook = () => {
    if (!isAuthenticated) {
      Alert.alert('Masuk diperlukan', 'Silakan masuk sebagai jamaah untuk memesan.', [
        { text: 'Batal', style: 'cancel' },
        { text: 'Masuk', onPress: () => navigateRoot(navigation, 'Login') },
      ]);
      return;
    }
    if (user?.role !== 'customer') {
      Alert.alert('Akses terbatas', 'Hanya akun jamaah yang dapat memesan muthowif.');
      return;
    }
    if (!bookingIntent?.can_submit) {
      const reason = bookingIntent?.reason;
      if (reason === 'missing_dates') {
        Alert.alert('Pilih tanggal', 'Kembali ke pencarian dan isi tanggal perjalanan terlebih dahulu.');
      } else if (reason === 'jadwal_tidak_tersedia') {
        Alert.alert('Tidak tersedia', 'Muthowif tidak tersedia pada tanggal yang dipilih.');
      } else {
        Alert.alert('Pilih tanggal', 'Isi tanggal perjalanan di halaman pencarian sebelum memesan.');
      }
      return;
    }
    navigation.navigate('BookingForm', {
      profileId: profile.id,
      profileName: profile.name,
      startDate: bookingIntent.start,
      endDate: bookingIntent.end,
      services,
    });
  };

  if (loading) {
    return (
      <View style={styles.container}>
        <SafeAreaView edges={['top']} style={styles.topBar}>
          <PressableScale onPress={() => navigation.goBack()} haptic="light" style={styles.backBtn}>
            <ChevronLeft size={22} color={colors.baytgo} strokeWidth={2.2} />
          </PressableScale>
        </SafeAreaView>
        <SkeletonList count={4} style={styles.skeleton} />
      </View>
    );
  }

  if (error || !profile) {
    return (
      <View style={styles.container}>
        <SafeAreaView edges={['top']} style={styles.topBar}>
          <PressableScale onPress={() => navigation.goBack()} haptic="light" style={styles.backBtn}>
            <ChevronLeft size={22} color={colors.baytgo} strokeWidth={2.2} />
          </PressableScale>
        </SafeAreaView>
        <ErrorState description={error || 'Profil tidak ditemukan'} onRetry={loadDetail} />
      </View>
    );
  }

  const langs = profile.languages || [];
  const pilgrimStat = profile.confirmed_bookings >= 500 ? '500+' : profile.confirmed_bookings > 0 ? String(profile.confirmed_bookings) : 'Belum ada';
  const canBook = bookingIntent?.can_submit && services.length > 0;
  const hasReviews = profile.reviews_count > 0 && profile.rating;
  const reviewStat = hasReviews ? `${profile.rating} (${profile.reviews_count})` : 'Belum ada';
  const avatarUri = resolveMediaUrl(profile.avatar);

  return (
    <View style={styles.container}>
      <SafeAreaView edges={['top']} style={styles.topBar}>
        <PressableScale onPress={() => navigation.goBack()} haptic="light" style={styles.backBtn}>
          <ChevronLeft size={22} color={colors.baytgo} strokeWidth={2.2} />
        </PressableScale>
      </SafeAreaView>

      <ScrollView contentContainerStyle={[styles.scroll, { paddingBottom: scrollBottomInset }]} showsVerticalScrollIndicator={false}>
        <View style={styles.profileHero}>
          <View style={styles.avatarOuter}>
            <View style={styles.avatarRing}>
              <AppImage uri={avatarUri} size={124} rounded={62} />
            </View>
            {profile.is_verified !== false ? (
              <View style={styles.verifiedBelow}>
                <ShieldCheck size={12} color={colors.success} strokeWidth={2.5} />
                <Text style={styles.verifiedBelowText}>Terverifikasi</Text>
              </View>
            ) : null}
          </View>
        </View>

        <View style={styles.content}>
          <Card style={styles.profileCard} padding={spacing.lg} elevated>
            <Text style={styles.profileName}>{profile.name}</Text>

            {profile.is_new ? (
              <View style={styles.newChip}>
                <Text style={styles.newChipText}>Baru di marketplace</Text>
              </View>
            ) : null}

            <View style={styles.profileRatingRow}>
              {hasReviews ? (
                <>
                  <Stars rating={parseFloat(profile.rating) || 0} size={15} />
                  <Text style={styles.profileRatingText}>
                    {profile.rating} · {profile.reviews_count} ulasan
                  </Text>
                </>
              ) : (
                <Text style={styles.profileRatingEmpty}>Belum ada ulasan</Text>
              )}
            </View>

            {profile.location ? (
              <View style={styles.locationRow}>
                <MapPin size={15} color={colors.baytgo} strokeWidth={2} />
                <Text style={styles.locationText}>{profile.location}</Text>
              </View>
            ) : null}

            {langs.length > 0 ? (
              <View style={styles.langRow}>
                {langs.map((lang) => (
                  <View key={lang} style={styles.langChip}>
                    <Text style={styles.langChipText}>{lang}</Text>
                  </View>
                ))}
              </View>
            ) : null}

            <View style={styles.statBar}>
              <StatCell icon={Briefcase} label="Pengalaman" value={profile.experience_summary || 'Belum diisi'} />
              <View style={styles.statDivider} />
              <StatCell icon={User} label="Jamaah" value={pilgrimStat} />
              <View style={styles.statDivider} />
              <StatCell icon={Star} label="Ulasan" value={reviewStat} />
            </View>
          </Card>

          {startDate ? (
            <Card
              style={[styles.dateBanner, bookingIntent?.reason === 'jadwal_tidak_tersedia' && styles.dateBannerWarn]}
              padding={spacing.md}
              elevated={false}
            >
              {bookingIntent?.reason === 'jadwal_tidak_tersedia' ? (
                <AlertCircle size={16} color={colors.warning} strokeWidth={2} />
              ) : (
                <Calendar size={16} color={colors.baytgo} strokeWidth={2} />
              )}
              <View style={styles.dateBannerContent}>
                <Text style={styles.dateBannerText}>
                  {startDate}{endDate && endDate !== startDate ? ` — ${endDate}` : ''}
                </Text>
                {bookingIntent?.reason === 'jadwal_tidak_tersedia' ? (
                  <Text style={styles.dateBannerWarnText}>Tidak tersedia pada tanggal ini</Text>
                ) : bookingIntent?.can_submit ? (
                  <Text style={styles.dateBannerOkText}>Jadwal tersedia</Text>
                ) : null}
              </View>
            </Card>
          ) : null}

          {services.length > 0 ? (
            <SectionCard title="Paket Layanan" subtitle="Grup atau private — pilih saat pemesanan" icon={Briefcase}>
              {services.map((service) => (
                <PackageCard key={service.id} service={service} />
              ))}
            </SectionCard>
          ) : null}

          <SectionCard
            title="Layanan Tambahan (Add-on)"
            subtitle={allAddOns.length > 0 ? `${allAddOns.length} opsi tersedia · pilih saat booking` : 'Belum ada add-on dipublikasikan'}
            icon={CirclePlus}
            iconBg={colors.goldLight}
          >
            {allAddOns.length > 0 ? (
              <View style={partStyles.addonList}>
                {allAddOns.map((addon, index) => (
                  <AddOnListItem key={addon.id} addon={addon} index={index} />
                ))}
              </View>
            ) : (
              <EmptyState
                variant="package"
                title="Belum ada add-on"
                description="Muthowif belum menambahkan layanan tambahan. Hotel same-day & transport sudah termasuk di paket jika tersedia."
              />
            )}
          </SectionCard>

          {profile.bio || (profile.specializations || []).length > 0 ? (
            <SectionCard title="Tentang Muthowif" icon={User}>
              {profile.bio ? <Text style={partStyles.bioText}>{profile.bio}</Text> : null}
              {(profile.specializations || []).length > 0 ? (
                <>
                  <Text style={partStyles.tagsLabel}>Spesialisasi</Text>
                  <View style={partStyles.tagsRow}>
                    {profile.specializations.map((tag) => (
                      <View key={tag} style={partStyles.tag}>
                        <Text style={partStyles.tagText}>{tag}</Text>
                      </View>
                    ))}
                  </View>
                </>
              ) : null}
            </SectionCard>
          ) : null}

          {portfolios.length > 0 ? (
            <SectionCard
              title="Galeri Portfolio"
              subtitle={portfoliosCount > portfolios.length ? `${portfoliosCount} album` : null}
              icon={Images}
              iconBg="#F3E8FF"
            >
              <FlashList
                horizontal
                data={portfolios}
                keyExtractor={(item) => String(item.id)}
                showsHorizontalScrollIndicator={false}
                estimatedItemSize={168}
                contentContainerStyle={partStyles.galleryList}
                renderItem={({ item }) => (
                  <PressableScale
                    onPress={() => setLightbox({ visible: true, images: item.images, index: 0, title: item.title })}
                    haptic="light"
                  >
                    <AppImage uri={resolveMediaUrl(item.cover_url)} style={partStyles.galleryImage} rounded={radius.sm} />
                  </PressableScale>
                )}
              />
            </SectionCard>
          ) : null}

          <SectionCard title="Ulasan Jamaah" icon={Star} iconBg={colors.goldLight}>
            {reviews.length === 0 ? (
              <Text style={partStyles.muted}>Belum ada ulasan untuk muthowif ini.</Text>
            ) : (
              <>
                <View style={partStyles.reviewSummary}>
                  <Text style={partStyles.reviewSummaryScore}>{profile.rating}</Text>
                  <Stars rating={parseFloat(profile.rating) || 0} size={16} />
                  <Text style={partStyles.reviewSummaryCount}>{profile.reviews_count} ulasan</Text>
                </View>
                {reviews.map((review) => (
                  <ReviewItem key={review.id} review={review} />
                ))}
              </>
            )}
          </SectionCard>

          <Card style={styles.trustBar} padding={spacing.lg} elevated={false}>
            <View style={styles.trustItem}>
              <ShieldCheck size={18} color={colors.success} strokeWidth={2} />
              <Text style={styles.trustText}>Identitas terverifikasi</Text>
            </View>
            <View style={styles.trustItem}>
              <Lock size={18} color={colors.success} strokeWidth={2} />
              <Text style={styles.trustText}>Pembayaran aman</Text>
            </View>
            <View style={styles.trustItem}>
              <Headphones size={18} color={colors.success} strokeWidth={2} />
              <Text style={styles.trustText}>Dukungan Bayt-GO</Text>
            </View>
          </Card>

          {blockedDates.length > 0 ? (
            <PressableScale onPress={() => setShowBlocked((v) => !v)} haptic="light">
              <Card style={styles.blockedToggle} padding={spacing.lg} elevated={false}>
                <Calendar size={18} color={colors.warning} strokeWidth={2} />
                <Text style={styles.blockedToggleText}>
                  {blockedDates.length} tanggal libur / tidak tersedia
                </Text>
                {showBlocked ? (
                  <ChevronUp size={18} color={colors.textSecondary} strokeWidth={2} />
                ) : (
                  <ChevronDown size={18} color={colors.textSecondary} strokeWidth={2} />
                )}
              </Card>
            </PressableScale>
          ) : null}

          {showBlocked && blockedDates.length > 0 ? (
            <View style={styles.blockedList}>
              {blockedDates.map((bd) => (
                <Card key={bd.date} style={styles.blockedItem} padding={spacing.md} elevated={false}>
                  <Text style={styles.blockedDate}>{bd.date}</Text>
                  {bd.note ? <Text style={styles.blockedNote}>{bd.note}</Text> : null}
                </Card>
              ))}
            </View>
          ) : null}
        </View>
      </ScrollView>

      <StickyFooter
        priceLabel="Mulai dari"
        priceValue={formatIdr(profile.start_price)}
        priceSuffix="/hari"
      >
        <Button
          label={canBook ? 'Pesan Muthowif' : isAuthenticated ? 'Pilih Tanggal Dulu' : 'Masuk & Pesan'}
          onPress={handleBook}
          icon={<Calendar size={18} color={colors.white} strokeWidth={2} />}
          disabled={isAuthenticated && !canBook}
        />
      </StickyFooter>

      <PortfolioLightbox
        visible={lightbox.visible}
        images={lightbox.images}
        index={lightbox.index}
        title={lightbox.title}
        onClose={() => setLightbox((s) => ({ ...s, visible: false }))}
        onChangeIndex={(idx) => setLightbox((s) => ({ ...s, index: idx }))}
      />
    </View>
  );
}

const styles = StyleSheet.create({
  container: { flex: 1, backgroundColor: colors.background },
  scroll: {},
  skeleton: { padding: layout.screenPadding, paddingTop: spacing.lg },
  topBar: {
    backgroundColor: colors.card,
    borderBottomWidth: 1,
    borderBottomColor: colors.border,
    paddingHorizontal: layout.screenPadding,
    paddingBottom: spacing.md,
  },
  backBtn: {
    width: 44,
    height: 44,
    borderRadius: radius.sm,
    backgroundColor: colors.background,
    alignItems: 'center',
    justifyContent: 'center',
    borderWidth: 1,
    borderColor: colors.border,
  },
  profileHero: { alignItems: 'center', marginTop: spacing.lg, marginBottom: spacing.sm },
  avatarOuter: { alignItems: 'center' },
  avatarRing: {
    padding: 4,
    borderRadius: 66,
    backgroundColor: colors.card,
    borderWidth: 3,
    borderColor: colors.goldLight,
  },
  verifiedBelow: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 5,
    marginTop: spacing.md,
    backgroundColor: colors.successLight,
    paddingHorizontal: spacing.md,
    paddingVertical: 5,
    borderRadius: radius.full,
    borderWidth: 1,
    borderColor: 'rgba(5,150,105,0.15)',
  },
  verifiedBelowText: { ...typography.small, color: colors.success },
  content: { paddingHorizontal: layout.screenPadding },
  profileCard: { alignItems: 'center', marginBottom: spacing.xs },
  profileName: { ...typography.title, fontSize: 22, color: colors.textPrimary, textAlign: 'center', marginTop: spacing.xs },
  newChip: { alignSelf: 'center', marginTop: spacing.sm, backgroundColor: colors.warningLight, paddingHorizontal: spacing.md, paddingVertical: 4, borderRadius: radius.full },
  newChipText: { ...typography.small, color: '#92400E', fontWeight: '700' },
  profileRatingRow: { flexDirection: 'row', alignItems: 'center', justifyContent: 'center', flexWrap: 'wrap', gap: spacing.sm, marginTop: spacing.md },
  profileRatingText: { ...typography.caption, fontFamily: 'PlusJakartaSans_700Bold', color: colors.slate700 },
  profileRatingEmpty: { ...typography.caption, color: colors.textMuted, fontWeight: '500' },
  locationRow: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 6,
    marginTop: spacing.md,
    backgroundColor: colors.background,
    paddingHorizontal: spacing.md,
    paddingVertical: spacing.sm,
    borderRadius: radius.sm,
    borderWidth: 1,
    borderColor: colors.border,
  },
  locationText: { ...typography.caption, fontFamily: 'PlusJakartaSans_700Bold', color: colors.slate700 },
  langRow: { flexDirection: 'row', flexWrap: 'wrap', gap: 6, marginTop: spacing.md, justifyContent: 'center' },
  langChip: { backgroundColor: colors.baytgoLight, borderRadius: radius.full, paddingHorizontal: spacing.md, paddingVertical: 5 },
  langChipText: { ...typography.small, color: colors.baytgo },
  statBar: {
    flexDirection: 'row',
    alignItems: 'stretch',
    marginTop: spacing.lg,
    paddingTop: spacing.lg,
    borderTopWidth: 1,
    borderTopColor: colors.border,
    width: '100%',
  },
  statDivider: { width: 1, backgroundColor: colors.border, marginVertical: 4 },
  dateBanner: { flexDirection: 'row', alignItems: 'flex-start', gap: spacing.md, marginTop: spacing.md, backgroundColor: colors.successLight, borderColor: '#A7F3D0' },
  dateBannerWarn: { backgroundColor: colors.warningLight, borderColor: '#FDE68A' },
  dateBannerContent: { flex: 1 },
  dateBannerText: { ...typography.caption, fontFamily: 'PlusJakartaSans_700Bold', color: colors.baytgo },
  dateBannerOkText: { marginTop: 2, ...typography.small, color: colors.success, fontWeight: '500' },
  dateBannerWarnText: { marginTop: 2, ...typography.small, color: colors.warning, fontWeight: '500' },
  trustBar: { marginTop: spacing.lg, gap: spacing.md },
  trustItem: { flexDirection: 'row', alignItems: 'center', gap: spacing.md },
  trustText: { ...typography.caption, color: colors.slate700, fontWeight: '600' },
  blockedToggle: { flexDirection: 'row', alignItems: 'center', gap: spacing.md, marginTop: spacing.md, backgroundColor: colors.warningLight, borderColor: '#FDE68A' },
  blockedToggleText: { flex: 1, ...typography.caption, fontFamily: 'PlusJakartaSans_700Bold', color: '#92400E' },
  blockedList: { marginTop: spacing.sm, gap: 6 },
  blockedItem: { borderColor: '#FDE68A' },
  blockedDate: { ...typography.caption, fontFamily: 'PlusJakartaSans_800ExtraBold', color: colors.textPrimary },
  blockedNote: { marginTop: 2, ...typography.small, color: colors.textSecondary, fontWeight: '500' },
});
