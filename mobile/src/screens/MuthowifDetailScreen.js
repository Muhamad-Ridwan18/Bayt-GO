import React, { useCallback, useEffect, useMemo, useRef, useState } from 'react';
import {
  View, Text, StyleSheet, ScrollView, Alert, Share,
} from 'react-native';
import { FlashList } from '@shopify/flash-list';
import { LinearGradient } from 'expo-linear-gradient';
import { SafeAreaView } from 'react-native-safe-area-context';
import {
  AlertCircle, Briefcase, Calendar, ChevronDown, ChevronLeft, ChevronUp,
  CirclePlus, GraduationCap, Headphones, Images, Lock, MapPin, Share2, ShieldCheck, Star, User,
} from 'lucide-react-native';
import { fetchMuthowifDetail, fetchMuthowifPortfolios } from '../api/directory';
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
import { WEB_BASE_URL } from '../config/api';
import { useHideTabBarOnFocus } from '../hooks/useHideTabBarOnFocus';
import { useSafeAreaInsets } from 'react-native-safe-area-context';
import { useLocale } from '../utils/locale';

export default function MuthowifDetailScreen({ navigation, route }) {
  const { token, isAuthenticated, user } = useAuth();
  const locale = useLocale(); const isEn = locale === 'en';
  const { id, startDate, endDate, autoBook } = route.params || {};

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
  const autoBookedRef = useRef(false);

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
      setError(err.message || (isEn ? 'Failed to load profile' : 'Gagal memuat profil'));
    } finally {
      setLoading(false);
    }
  }, [token, id, startDate, endDate]);

  useEffect(() => { loadDetail(); }, [loadDetail]);
  useHideTabBarOnFocus(navigation);
  const insets = useSafeAreaInsets();
  const scrollBottomInset = 96 + Math.max(insets.bottom, spacing.md);

  const loadAllPortfolios = useCallback(async () => {
    try {
      const data = await fetchMuthowifPortfolios({ token, id });
      const next = data.data || [];
      setPortfolios(next);
      setPortfoliosCount(data.total || next.length);
    } catch { /* keep preview albums */ }
  }, [token, id]);

  const goToBookingForm = useCallback(() => {
    if (!profile) return;
    navigation.navigate('BookingForm', {
      profileId: profile.id,
      profileName: profile.name,
      startDate: bookingIntent.start,
      endDate: bookingIntent.end,
      services,
    });
  }, [navigation, profile, bookingIntent, services]);

  const handleBook = () => {
    if (!isAuthenticated) {
      Alert.alert(isEn ? 'Login required' : 'Masuk diperlukan', isEn ? 'Please log in as a pilgrim to book.' : 'Silakan masuk sebagai jamaah untuk memesan.', [
        { text: isEn ? 'Cancel' : 'Batal', style: 'cancel' },
        { text: isEn ? 'Login' : 'Masuk', onPress: () => navigateRoot(navigation, 'Login') },
      ]);
      return;
    }
    if (user?.role !== 'customer') {
      Alert.alert(isEn ? 'Restricted access' : 'Akses terbatas', isEn ? 'Only pilgrim accounts can book a muthowif.' : 'Hanya akun jamaah yang dapat memesan muthowif.');
      return;
    }
    if (!bookingIntent?.can_submit) {
      const reason = bookingIntent?.reason;
      if (reason === 'missing_dates') {
        Alert.alert(isEn ? 'Select dates' : 'Pilih tanggal', isEn ? 'Go back to search and fill in travel dates first.' : 'Kembali ke pencarian dan isi tanggal perjalanan terlebih dahulu.');
      } else if (reason === 'jadwal_tidak_tersedia') {
        Alert.alert(isEn ? 'Not available' : 'Tidak tersedia', isEn ? 'Muthowif is not available on the selected dates.' : 'Muthowif tidak tersedia pada tanggal yang dipilih.');
      } else {
        Alert.alert(isEn ? 'Select dates' : 'Pilih tanggal', isEn ? 'Fill in travel dates on the search page before booking.' : 'Isi tanggal perjalanan di halaman pencarian sebelum memesan.');
      }
      return;
    }
    goToBookingForm();
  };

  useEffect(() => {
    if (!autoBook || autoBookedRef.current || loading || !profile) return;
    if (!isAuthenticated || user?.role !== 'customer') return;
    if (!bookingIntent?.can_submit || services.length === 0) return;
    autoBookedRef.current = true;
    goToBookingForm();
  }, [autoBook, loading, profile, isAuthenticated, user?.role, bookingIntent, services, goToBookingForm]);

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
        <ErrorState description={error || (isEn ? 'Profile not found' : 'Profil tidak ditemukan')} onRetry={loadDetail} />
      </View>
    );
  }

  const langs = profile.languages || [];
  const pilgrimStat = profile.confirmed_bookings >= 500 ? '500+' : profile.confirmed_bookings > 0 ? String(profile.confirmed_bookings) : (isEn ? 'None yet' : 'Belum ada');
  const canBook = bookingIntent?.can_submit && services.length > 0;
  const hasReviews = profile.reviews_count > 0 && profile.rating;
  const reviewStat = hasReviews ? `${profile.rating} (${profile.reviews_count})` : (isEn ? 'None yet' : 'Belum ada');
  const profileKey = profile.slug || profile.id;
  const profileUrl = profileKey
    ? `${WEB_BASE_URL.replace(/\/$/, '')}/layanan/${profileKey}`
    : null;

  const handleShare = async () => {
    if (!profileUrl) return;
    try {
      await Share.share({
        message: isEn ? `Check out ${profile.name}'s muthowif profile on BaytGo: ${profileUrl}` : `Lihat profil muthowif ${profile.name} di BaytGo: ${profileUrl}`,
        url: profileUrl,
      });
    } catch { /* dismissed */ }
  };

  const avatarUri = resolveMediaUrl(profile.avatar);

  return (
    <View style={styles.container}>
      <SafeAreaView edges={['top']} style={styles.topBar}>
        <View style={styles.topBarRow}>
          <PressableScale onPress={() => navigation.goBack()} haptic="light" style={styles.backBtn}>
            <ChevronLeft size={22} color={colors.baytgo} strokeWidth={2.2} />
          </PressableScale>
          {profileUrl ? (
            <PressableScale onPress={handleShare} haptic="light" style={styles.backBtn}>
              <Share2 size={20} color={colors.baytgo} strokeWidth={2} />
            </PressableScale>
          ) : null}
        </View>
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
                <Text style={styles.verifiedBelowText}>{isEn ? 'Verified' : 'Terverifikasi'}</Text>
              </View>
            ) : null}
          </View>
        </View>

        <View style={styles.content}>
          <Card style={styles.profileCard} padding={spacing.lg} elevated>
            <Text style={styles.profileName}>{profile.name}</Text>

            {profile.is_new ? (
              <View style={styles.newChip}>
                <Text style={styles.newChipText}>{isEn ? 'New on marketplace' : 'Baru di marketplace'}</Text>
              </View>
            ) : null}

            <View style={styles.profileRatingRow}>
              {hasReviews ? (
                <>
                  <Stars rating={parseFloat(profile.rating) || 0} size={15} />
                  <Text style={styles.profileRatingText}>
                    {profile.rating} · {profile.reviews_count} {isEn ? 'reviews' : 'ulasan'}
                  </Text>
                </>
              ) : (
                <Text style={styles.profileRatingEmpty}>{isEn ? 'No reviews yet' : 'Belum ada ulasan'}</Text>
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
              <StatCell icon={Briefcase} label={isEn ? 'Experience' : 'Pengalaman'} value={profile.experience_summary || (isEn ? 'Not filled' : 'Belum diisi')} />
              <View style={styles.statDivider} />
              <StatCell icon={User} label={isEn ? 'Pilgrims' : 'Jamaah'} value={pilgrimStat} />
              <View style={styles.statDivider} />
              <StatCell icon={Star} label={isEn ? 'Reviews' : 'Ulasan'} value={reviewStat} />
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
                  <Text style={styles.dateBannerWarnText}>{isEn ? 'Not available on these dates' : 'Tidak tersedia pada tanggal ini'}</Text>
                ) : bookingIntent?.can_submit ? (
                  <Text style={styles.dateBannerOkText}>{isEn ? 'Schedule available' : 'Jadwal tersedia'}</Text>
                ) : null}
              </View>
            </Card>
          ) : null}

          {services.length > 0 ? (
            <SectionCard title={isEn ? 'Service Packages' : 'Paket Layanan'} subtitle={isEn ? 'Group or private — choose when booking' : 'Grup atau private — pilih saat pemesanan'} icon={Briefcase}>
              {services.map((service) => (
                <PackageCard key={service.id} service={service} />
              ))}
            </SectionCard>
          ) : null}

          <SectionCard
            title={isEn ? 'Add-on Services' : 'Layanan Tambahan (Add-on)'}
            subtitle={allAddOns.length > 0 ? (isEn ? `${allAddOns.length} options available · select when booking` : `${allAddOns.length} opsi tersedia · pilih saat booking`) : (isEn ? 'No add-ons published yet' : 'Belum ada add-on dipublikasikan')}
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
                title={isEn ? 'No add-ons yet' : 'Belum ada add-on'}
                description={isEn ? 'Muthowif has not added extra services. Same-day hotel & transport are included in the package if available.' : 'Muthowif belum menambahkan layanan tambahan. Hotel same-day & transport sudah termasuk di paket jika tersedia.'}
              />
            )}
          </SectionCard>

          {profile.bio || (profile.specializations || []).length > 0 ? (
            <SectionCard title={isEn ? 'About muthowif' : 'Tentang muthowif'} icon={User}>
              {profile.bio ? <Text style={partStyles.bioText}>{profile.bio}</Text> : null}
              {(profile.specializations || []).length > 0 ? (
                <>
                  <Text style={partStyles.tagsLabel}>{isEn ? 'Specializations' : 'Spesialisasi'}</Text>
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

          <SectionCard title={isEn ? 'Education & experience' : 'Studi & pengalaman'} icon={GraduationCap} iconBg={colors.goldLight}>
            {(profile.educations || []).length === 0 && (profile.work_experiences || []).length === 0 ? (
              <Text style={partStyles.muted}>{isEn ? 'Not filled by muthowif.' : 'Belum diisi oleh muthowif.'}</Text>
            ) : (
              <View style={styles.timeline}>
                {(profile.educations || []).length > 0 ? (
                  <View style={styles.timelineBlock}>
                    <Text style={styles.timelineLabel}>{isEn ? 'Education' : 'Pendidikan'}</Text>
                    {(profile.educations || []).map((item) => (
                      <Text key={item} style={styles.timelineItem}>{item}</Text>
                    ))}
                  </View>
                ) : null}
                {(profile.work_experiences || []).length > 0 ? (
                  <View style={styles.timelineBlock}>
                    <Text style={styles.timelineLabel}>{isEn ? 'Experience' : 'Pengalaman'}</Text>
                    {(profile.work_experiences || []).map((item) => (
                      <Text key={item} style={styles.timelineItem}>{item}</Text>
                    ))}
                  </View>
                ) : null}
              </View>
            )}
          </SectionCard>

          <SectionCard
            title={isEn ? 'Photo gallery' : 'Galeri foto'}
            subtitle={portfoliosCount > 0 ? `${portfoliosCount} ${isEn ? 'albums' : 'album'}` : null}
            icon={Images}
            iconBg="#F3E8FF"
          >
            {portfolios.length === 0 ? (
              <Text style={partStyles.muted}>{isEn ? 'Muthowif has not added portfolio photos.' : 'Muthowif belum menambahkan foto portfolio.'}</Text>
            ) : (
              <>
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
                      {item.title ? <Text style={styles.galleryTitle} numberOfLines={1}>{item.title}</Text> : null}
                    </PressableScale>
                  )}
                />
                {portfoliosCount > portfolios.length ? (
                  <PressableScale onPress={loadAllPortfolios} haptic="light" style={styles.seeAllBtn}>
                    <Text style={styles.seeAllText}>{isEn ? `See all photos (${portfoliosCount})` : `Lihat semua foto (${portfoliosCount})`}</Text>
                  </PressableScale>
                ) : null}
              </>
            )}
          </SectionCard>

          <SectionCard title={isEn ? 'Pilgrim Reviews' : 'Ulasan Jamaah'} icon={Star} iconBg={colors.goldLight}>
            {reviews.length === 0 ? (
              <Text style={partStyles.muted}>{isEn ? 'No reviews for this muthowif yet.' : 'Belum ada ulasan untuk muthowif ini.'}</Text>
            ) : (
              <>
                <View style={partStyles.reviewSummary}>
                  <Text style={partStyles.reviewSummaryScore}>{profile.rating}</Text>
                  <Stars rating={parseFloat(profile.rating) || 0} size={16} />
                  <Text style={partStyles.reviewSummaryCount}>{profile.reviews_count} {isEn ? 'reviews' : 'ulasan'}</Text>
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
              <Text style={styles.trustText}>{isEn ? 'Verified identity' : 'Identitas terverifikasi'}</Text>
            </View>
            <View style={styles.trustItem}>
              <Lock size={18} color={colors.success} strokeWidth={2} />
              <Text style={styles.trustText}>{isEn ? 'Secure payment' : 'Pembayaran aman'}</Text>
            </View>
            <View style={styles.trustItem}>
              <Headphones size={18} color={colors.success} strokeWidth={2} />
              <Text style={styles.trustText}>{isEn ? 'Bayt-GO support' : 'Dukungan Bayt-GO'}</Text>
            </View>
          </Card>

          <SectionCard
            title={isEn ? 'Unavailable schedule (off days)' : 'Jadwal tidak tersedia (libur)'}
            subtitle={isEn ? 'The following dates the muthowif is unavailable. Outside of these, the schedule may already be full — use date search in the listing.' : 'Tanggal berikut muthowif tidak tersedia. Di luar itu, jadwal bisa sudah terisi — gunakan pencarian tanggal di daftar.'}
            icon={Calendar}
            iconBg={colors.warningLight}
          >
            {blockedDates.length === 0 ? (
              <Text style={partStyles.muted}>{isEn ? 'No off days announced (or all dates have passed).' : 'Belum ada tanggal libur yang diumumkan (atau semua tanggal sudah lewat).'}</Text>
            ) : (
              <>
                <PressableScale onPress={() => setShowBlocked((v) => !v)} haptic="light">
                  <View style={styles.blockedToggleRow}>
                    <Text style={styles.blockedToggleText}>
                      {blockedDates.length} {isEn ? 'off days / unavailable' : 'tanggal libur / tidak tersedia'}
                    </Text>
                    {showBlocked ? (
                      <ChevronUp size={18} color={colors.textSecondary} strokeWidth={2} />
                    ) : (
                      <ChevronDown size={18} color={colors.textSecondary} strokeWidth={2} />
                    )}
                  </View>
                </PressableScale>
                {showBlocked ? (
                  <View style={styles.blockedListInner}>
                    {blockedDates.map((bd) => (
                      <View key={bd.date} style={styles.blockedItemInner}>
                        <Text style={styles.blockedDate}>{bd.date}</Text>
                        {bd.note ? <Text style={styles.blockedNote}>{bd.note}</Text> : null}
                      </View>
                    ))}
                  </View>
                ) : null}
              </>
            )}
          </SectionCard>
        </View>
      </ScrollView>

      <StickyFooter
        priceLabel={isEn ? 'Starting from' : 'Mulai dari'}
        priceValue={formatIdr(profile.start_price)}
        priceSuffix={isEn ? '/day' : '/hari'}
      >
        <Button
          label={canBook ? (isEn ? 'Book Muthowif' : 'Pesan Muthowif') : isAuthenticated ? (isEn ? 'Select Dates First' : 'Pilih Tanggal Dulu') : (isEn ? 'Login & Book' : 'Masuk & Pesan')}
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
  topBarRow: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
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
  timeline: { gap: spacing.lg },
  timelineBlock: { gap: 6 },
  timelineLabel: {
    ...typography.label,
    fontSize: 11,
    color: colors.baytgo,
    textTransform: 'uppercase',
    fontFamily: 'PlusJakartaSans_800ExtraBold',
  },
  timelineItem: { ...typography.caption, fontSize: 13, lineHeight: 20, color: colors.slate700, fontWeight: '500' },
  galleryTitle: {
    marginTop: spacing.sm,
    width: 168,
    ...typography.small,
    color: colors.slate700,
    fontWeight: '700',
  },
  seeAllBtn: { marginTop: spacing.md },
  seeAllText: { ...typography.caption, color: colors.baytgo, fontFamily: 'PlusJakartaSans_700Bold' },
  blockedToggleRow: { flexDirection: 'row', alignItems: 'center', gap: spacing.md },
  blockedToggleText: { flex: 1, ...typography.caption, fontFamily: 'PlusJakartaSans_700Bold', color: '#92400E' },
  blockedListInner: { marginTop: spacing.md, gap: 6 },
  blockedItemInner: {
    backgroundColor: colors.warningLight,
    borderRadius: radius.sm,
    padding: spacing.md,
  },
  blockedDate: { ...typography.caption, fontFamily: 'PlusJakartaSans_800ExtraBold', color: colors.textPrimary },
  blockedNote: { marginTop: 2, ...typography.small, color: colors.textSecondary, fontWeight: '500' },
});
