import React, { useCallback, useEffect, useMemo, useState } from 'react';
import {
  View,
  Text,
  StyleSheet,
  ScrollView,
  Image,
  TouchableOpacity,
  ActivityIndicator,
  Alert,
  Modal,
  Dimensions,
  FlatList,
} from 'react-native';
import { LinearGradient } from 'expo-linear-gradient';
import { Ionicons } from '@expo/vector-icons';
import { SafeAreaView } from 'react-native-safe-area-context';
import { fetchMuthowifDetail } from '../api/directory';
import { useAuth } from '../context/AuthContext';
import { navigateRoot } from '../navigation/rootNavigation';
import { colors } from '../theme/colors';
import { formatIdr } from '../utils/format';

const { width: SCREEN_W } = Dimensions.get('window');
const HERO_H = Math.min(SCREEN_W * 0.78, 340);

const ADDON_ICONS = ['star-outline', 'car-outline', 'bed-outline', 'restaurant-outline', 'camera-outline', 'map-outline'];

function AddOnListItem({ addon, index }) {
  const icon = ADDON_ICONS[index % ADDON_ICONS.length];

  return (
    <View style={styles.addonRow}>
      <LinearGradient
        colors={['#FFFBEB', '#FEF3C7']}
        style={styles.addonIconWrap}
      >
        <Ionicons name={icon} size={20} color={colors.gold} />
      </LinearGradient>
      <View style={styles.addonRowBody}>
        <Text style={styles.addonRowName}>{addon.name}</Text>
        <Text style={styles.addonRowHint}>Opsional · dipilih saat booking</Text>
      </View>
      <View style={styles.addonPricePill}>
        <Text style={styles.addonPricePillText}>{formatIdr(addon.price)}</Text>
      </View>
    </View>
  );
}

function StatCell({ icon, label, value }) {
  return (
    <View style={styles.statCell}>
      <Ionicons name={icon} size={15} color={colors.baytgo} />
      <Text style={styles.statCellLabel}>{label}</Text>
      <Text style={styles.statCellValue} numberOfLines={3}>{value}</Text>
    </View>
  );
}

function Stars({ rating, size = 14 }) {
  return (
    <View style={styles.starsRow}>
      {Array.from({ length: 5 }).map((_, i) => (
        <Ionicons
          key={i}
          name={i < Math.round(rating) ? 'star' : 'star-outline'}
          size={size}
          color={colors.gold}
        />
      ))}
    </View>
  );
}

function SectionCard({ title, subtitle, icon, iconBg, children }) {
  return (
    <View style={styles.sectionCard}>
      <View style={styles.sectionHeader}>
        {icon ? (
          <View style={[styles.sectionIcon, { backgroundColor: iconBg || colors.baytgoLight }]}>
            <Ionicons name={icon} size={20} color={colors.baytgo} />
          </View>
        ) : null}
        <View style={styles.sectionHeaderText}>
          <Text style={styles.sectionTitle}>{title}</Text>
          {subtitle ? <Text style={styles.sectionSubtitle}>{subtitle}</Text> : null}
        </View>
      </View>
      {children}
    </View>
  );
}

function PackageCard({ service }) {
  const isPrivate = service.type === 'private';
  const accent = isPrivate ? colors.gold : colors.baytgo;
  const gradient = isPrivate ? ['#FFFBEB', '#FFFFFF'] : ['#F0F7F4', '#FFFFFF'];
  const serviceAddOns = service.add_ons || [];

  return (
    <View style={[styles.packageCard, isPrivate ? styles.packagePrivate : styles.packageGroup]}>
      <LinearGradient colors={gradient} style={styles.packageGradient}>
        <View style={styles.packageTopRow}>
          <View style={[styles.packageBadge, { backgroundColor: accent }]}>
            <Text style={styles.packageTypeLabel}>{service.type_label || service.type}</Text>
          </View>
          {service.has_hotel_addon ? (
            <View style={styles.packageMiniBadge}>
              <Ionicons name="bed-outline" size={12} color={colors.baytgo} />
              <Text style={styles.packageMiniText}>Hotel</Text>
            </View>
          ) : null}
          {service.has_transport_addon ? (
            <View style={styles.packageMiniBadge}>
              <Ionicons name="car-outline" size={12} color={colors.baytgo} />
              <Text style={styles.packageMiniText}>Transport</Text>
            </View>
          ) : null}
        </View>

        <Text style={[styles.packagePrice, { color: accent }]}>
          {service.price ? formatIdr(service.price) : 'Hubungi kami'}
          {service.price ? <Text style={styles.packagePerDay}> / hari</Text> : null}
        </Text>

        {service.min_pilgrims && service.max_pilgrims ? (
          <View style={styles.packagePaxRow}>
            <Ionicons name="people-outline" size={14} color={colors.slate500} />
            <Text style={styles.packagePax}>{service.min_pilgrims}–{service.max_pilgrims} jamaah</Text>
          </View>
        ) : null}

        {service.description ? (
          <Text style={styles.packageDesc}>{service.description}</Text>
        ) : null}

        <View style={styles.featureList}>
          {(service.features || []).map((feature) => (
            <View key={feature} style={styles.featureRow}>
              <Ionicons name="checkmark-circle" size={16} color={accent} />
              <Text style={styles.featureText}>{feature}</Text>
            </View>
          ))}
        </View>

        {serviceAddOns.length > 0 ? (
          <View style={styles.packageAddonBlock}>
            <Text style={styles.packageAddonTitle}>Add-on paket ini</Text>
            {serviceAddOns.map((addon, i) => (
              <View key={addon.id} style={styles.packageAddonRow}>
                <Text style={styles.packageAddonName} numberOfLines={1}>{addon.name}</Text>
                <Text style={styles.packageAddonPrice}>{formatIdr(addon.price)}</Text>
              </View>
            ))}
          </View>
        ) : null}
      </LinearGradient>
    </View>
  );
}

function ReviewItem({ review }) {
  return (
    <View style={styles.reviewCard}>
      <View style={styles.reviewHeader}>
        <Image source={{ uri: review.customer_avatar }} style={styles.reviewAvatar} />
        <View style={styles.reviewMeta}>
          <Text style={styles.reviewName}>{review.customer_name}</Text>
          <Stars rating={review.rating} size={12} />
        </View>
      </View>
      {review.comment ? <Text style={styles.reviewComment}>{review.comment}</Text> : null}
      <Text style={styles.reviewTime}>{review.created_at}</Text>
    </View>
  );
}

function PortfolioLightbox({ visible, images, index, title, onClose, onChangeIndex }) {
  if (!visible || !images?.length) return null;

  return (
    <Modal visible={visible} transparent animationType="fade" onRequestClose={onClose}>
      <View style={styles.lightboxOverlay}>
        <TouchableOpacity style={styles.lightboxClose} onPress={onClose}>
          <Ionicons name="close" size={24} color={colors.white} />
        </TouchableOpacity>
        {images.length > 1 ? (
          <>
            <TouchableOpacity
              style={[styles.lightboxNav, styles.lightboxNavLeft]}
              onPress={() => onChangeIndex((index - 1 + images.length) % images.length)}
            >
              <Ionicons name="chevron-back" size={28} color={colors.white} />
            </TouchableOpacity>
            <TouchableOpacity
              style={[styles.lightboxNav, styles.lightboxNavRight]}
              onPress={() => onChangeIndex((index + 1) % images.length)}
            >
              <Ionicons name="chevron-forward" size={28} color={colors.white} />
            </TouchableOpacity>
          </>
        ) : null}
        <Image source={{ uri: images[index] }} style={styles.lightboxImage} resizeMode="contain" />
        {title ? <Text style={styles.lightboxTitle}>{title}</Text> : null}
        {images.length > 1 ? (
          <Text style={styles.lightboxCounter}>{index + 1} / {images.length}</Text>
        ) : null}
      </View>
    </Modal>
  );
}

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

  useEffect(() => {
    loadDetail();
  }, [loadDetail]);

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

  const openLightbox = (images, title, startIndex = 0) => {
    setLightbox({ visible: true, images, index: startIndex, title });
  };

  if (loading) {
    return (
      <View style={styles.container}>
        <SafeAreaView edges={['top']} style={styles.loadingSafe}>
          <TouchableOpacity style={styles.backBtn} onPress={() => navigation.goBack()}>
            <Ionicons name="chevron-back" size={22} color={colors.baytgo} />
          </TouchableOpacity>
        </SafeAreaView>
        <ActivityIndicator color={colors.baytgo} style={styles.loader} />
      </View>
    );
  }

  if (error || !profile) {
    return (
      <View style={styles.container}>
        <SafeAreaView edges={['top']} style={styles.loadingSafe}>
          <TouchableOpacity style={styles.backBtn} onPress={() => navigation.goBack()}>
            <Ionicons name="chevron-back" size={22} color={colors.baytgo} />
          </TouchableOpacity>
        </SafeAreaView>
        <View style={styles.empty}>
          <Text style={styles.emptyText}>{error || 'Profil tidak ditemukan'}</Text>
          <TouchableOpacity onPress={loadDetail}>
            <Text style={styles.retry}>Coba lagi</Text>
          </TouchableOpacity>
        </View>
      </View>
    );
  }

  const langs = profile.languages || [];
  const pilgrimStat = profile.confirmed_bookings >= 500
    ? '500+'
    : profile.confirmed_bookings > 0
      ? String(profile.confirmed_bookings)
      : 'Belum ada';
  const canBook = bookingIntent?.can_submit && services.length > 0;
  const hasReviews = profile.reviews_count > 0 && profile.rating;
  const reviewStat = hasReviews ? `${profile.rating} (${profile.reviews_count})` : 'Belum ada';
  const experienceStat = profile.experience_summary || 'Belum diisi';

  return (
    <View style={styles.container}>
      <ScrollView contentContainerStyle={styles.scroll} showsVerticalScrollIndicator={false}>
        <View style={styles.heroWrap}>
          <Image source={{ uri: profile.avatar }} style={styles.heroImage} />
          <LinearGradient
            colors={['rgba(15,34,29,0.25)', 'transparent']}
            locations={[0, 0.45]}
            style={StyleSheet.absoluteFill}
          />
          <SafeAreaView edges={['top']} style={styles.heroTopBar}>
            <TouchableOpacity style={styles.backBtnHero} onPress={() => navigation.goBack()}>
              <Ionicons name="chevron-back" size={22} color={colors.white} />
            </TouchableOpacity>
          </SafeAreaView>
        </View>

        <View style={styles.content}>
          <View style={styles.profileCard}>
            <View style={styles.profileTitleRow}>
              <Text style={styles.profileName}>{profile.name}</Text>
              {profile.is_verified !== false ? (
                <View style={styles.verifiedChip}>
                  <Ionicons name="shield-checkmark" size={11} color={colors.emerald600} />
                  <Text style={styles.verifiedChipText}>Terverifikasi</Text>
                </View>
              ) : null}
            </View>

            {profile.is_new ? (
              <View style={styles.newChip}>
                <Text style={styles.newChipText}>Baru di marketplace</Text>
              </View>
            ) : null}

            {profile.location ? (
              <View style={styles.locationBadge}>
                <Text style={styles.locationBadgeLabel}>Lokasi kerja</Text>
                <View style={styles.locationBadgeRow}>
                  <Ionicons name="location" size={14} color="#0369A1" />
                  <Text style={styles.locationBadgeText}>{profile.location}</Text>
                </View>
              </View>
            ) : null}

            <View style={styles.profileRatingRow}>
              {hasReviews ? (
                <>
                  <Stars rating={parseFloat(profile.rating) || 0} size={14} />
                  <Text style={styles.profileRatingText}>
                    {profile.rating} · {profile.reviews_count} ulasan
                  </Text>
                </>
              ) : (
                <Text style={styles.profileRatingEmpty}>Belum ada ulasan</Text>
              )}
            </View>

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
              <StatCell icon="briefcase-outline" label="Pengalaman" value={experienceStat} />
              <View style={styles.statDivider} />
              <StatCell icon="people-outline" label="Jamaah" value={pilgrimStat} />
              <View style={styles.statDivider} />
              <StatCell icon="star-outline" label="Ulasan" value={reviewStat} />
            </View>
          </View>

          {startDate ? (
            <View style={[
              styles.dateBanner,
              bookingIntent?.reason === 'jadwal_tidak_tersedia' && styles.dateBannerWarn,
            ]}>
              <Ionicons
                name={bookingIntent?.reason === 'jadwal_tidak_tersedia' ? 'alert-circle-outline' : 'calendar-outline'}
                size={16}
                color={bookingIntent?.reason === 'jadwal_tidak_tersedia' ? '#B45309' : colors.baytgo}
              />
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
            </View>
          ) : null}

          {services.length > 0 ? (
            <SectionCard
              title="Paket Layanan"
              subtitle="Grup atau private — pilih saat pemesanan"
              icon="briefcase-outline"
            >
              {services.map((service) => (
                <PackageCard key={service.id} service={service} />
              ))}
            </SectionCard>
          ) : null}

          <SectionCard
            title="Layanan Tambahan (Add-on)"
            subtitle={allAddOns.length > 0
              ? `${allAddOns.length} opsi tersedia · pilih saat booking`
              : 'Belum ada add-on dipublikasikan'}
            icon="add-circle-outline"
            iconBg="#FEF3C7"
          >
            {allAddOns.length > 0 ? (
              <View style={styles.addonList}>
                {allAddOns.map((addon, index) => (
                  <AddOnListItem key={addon.id} addon={addon} index={index} />
                ))}
              </View>
            ) : (
              <View style={styles.addonEmpty}>
                <Ionicons name="cube-outline" size={28} color={colors.slate400} />
                <Text style={styles.addonEmptyText}>
                  Muthowif belum menambahkan layanan tambahan. Hotel same-day & transport sudah termasuk di paket jika tersedia.
                </Text>
              </View>
            )}
          </SectionCard>

          {profile.bio || (profile.specializations || []).length > 0 ? (
            <SectionCard title="Tentang Muthowif" icon="person-outline">
              {profile.bio ? (
                <Text style={styles.bioText}>{profile.bio}</Text>
              ) : null}
              {(profile.specializations || []).length > 0 ? (
                <>
                  <Text style={styles.tagsLabel}>Spesialisasi</Text>
                  <View style={styles.tagsRow}>
                    {profile.specializations.map((tag) => (
                      <View key={tag} style={styles.tag}>
                        <Text style={styles.tagText}>{tag}</Text>
                      </View>
                    ))}
                  </View>
                </>
              ) : null}
            </SectionCard>
          ) : null}

          {(profile.educations?.length > 0 || profile.work_experiences?.length > 0) ? (
            <SectionCard title="Riwayat & Pengalaman" icon="school-outline" iconBg="#E0F2FE">
              {profile.educations?.length > 0 ? (
                <View style={styles.timelineBlock}>
                  <View style={styles.timelineDot} />
                  <Text style={styles.timelineHeading}>Pendidikan</Text>
                  {profile.educations.map((item) => (
                    <Text key={item} style={styles.timelineItem}>• {item}</Text>
                  ))}
                </View>
              ) : null}
              {profile.work_experiences?.length > 0 ? (
                <View style={[styles.timelineBlock, profile.educations?.length > 0 && styles.timelineBlockSpaced]}>
                  <View style={[styles.timelineDot, styles.timelineDotGold]} />
                  <Text style={[styles.timelineHeading, styles.timelineHeadingGold]}>Pengalaman</Text>
                  {profile.work_experiences.map((item) => (
                    <Text key={item} style={styles.timelineItem}>• {item}</Text>
                  ))}
                </View>
              ) : null}
            </SectionCard>
          ) : null}

          {portfolios.length > 0 ? (
            <SectionCard
              title="Galeri Portfolio"
              subtitle={portfoliosCount > portfolios.length ? `${portfoliosCount} album` : null}
              icon="images-outline"
              iconBg="#F3E8FF"
            >
              <FlatList
                horizontal
                data={portfolios}
                keyExtractor={(item) => item.id}
                showsHorizontalScrollIndicator={false}
                contentContainerStyle={styles.galleryList}
                renderItem={({ item }) => (
                  <TouchableOpacity
                    style={styles.galleryItem}
                    activeOpacity={0.9}
                    onPress={() => openLightbox(item.images, item.title)}
                  >
                    <Image source={{ uri: item.cover_url }} style={styles.galleryImage} />
                    {item.title ? (
                      <LinearGradient
                        colors={['transparent', 'rgba(0,0,0,0.7)']}
                        style={styles.galleryOverlay}
                      >
                        <Text style={styles.galleryTitle} numberOfLines={2}>{item.title}</Text>
                      </LinearGradient>
                    ) : null}
                  </TouchableOpacity>
                )}
              />
            </SectionCard>
          ) : null}

          <SectionCard title="Ulasan Jamaah" icon="star-outline" iconBg="#FEF3C7">
            {reviews.length === 0 ? (
              <Text style={styles.muted}>Belum ada ulasan untuk muthowif ini.</Text>
            ) : (
              <>
                <View style={styles.reviewSummary}>
                  <Text style={styles.reviewSummaryScore}>{profile.rating}</Text>
                  <Stars rating={parseFloat(profile.rating) || 0} size={16} />
                  <Text style={styles.reviewSummaryCount}>{profile.reviews_count} ulasan</Text>
                </View>
                {reviews.map((review) => (
                  <ReviewItem key={review.id} review={review} />
                ))}
              </>
            )}
          </SectionCard>

          <View style={styles.trustBar}>
            <View style={styles.trustItem}>
              <Ionicons name="shield-checkmark" size={18} color={colors.emerald600} />
              <Text style={styles.trustText}>Identitas terverifikasi</Text>
            </View>
            <View style={styles.trustItem}>
              <Ionicons name="lock-closed" size={18} color={colors.emerald600} />
              <Text style={styles.trustText}>Pembayaran aman</Text>
            </View>
            <View style={styles.trustItem}>
              <Ionicons name="headset" size={18} color={colors.emerald600} />
              <Text style={styles.trustText}>Dukungan Bayt-GO</Text>
            </View>
          </View>

          {blockedDates.length > 0 ? (
            <TouchableOpacity
              style={styles.blockedToggle}
              onPress={() => setShowBlocked((v) => !v)}
              activeOpacity={0.8}
            >
              <Ionicons name="calendar" size={18} color="#B45309" />
              <Text style={styles.blockedToggleText}>
                {blockedDates.length} tanggal libur / tidak tersedia
              </Text>
              <Ionicons name={showBlocked ? 'chevron-up' : 'chevron-down'} size={18} color={colors.slate500} />
            </TouchableOpacity>
          ) : null}

          {showBlocked && blockedDates.length > 0 ? (
            <View style={styles.blockedList}>
              {blockedDates.map((bd) => (
                <View key={bd.date} style={styles.blockedItem}>
                  <Text style={styles.blockedDate}>{bd.date}</Text>
                  {bd.note ? <Text style={styles.blockedNote}>{bd.note}</Text> : null}
                </View>
              ))}
            </View>
          ) : null}
        </View>
      </ScrollView>

      <View style={styles.footer}>
        <LinearGradient
          colors={['rgba(249,247,242,0)', 'rgba(249,247,242,0.95)', colors.canvas]}
          style={styles.footerFade}
          pointerEvents="none"
        />
        <View style={styles.footerInner}>
          <View style={styles.footerPrice}>
            <Text style={styles.footerPriceLabel}>Mulai dari</Text>
            <Text style={styles.footerPriceValue}>
              {formatIdr(profile.start_price)}
              <Text style={styles.footerPriceDay}>/hari</Text>
            </Text>
          </View>
          <TouchableOpacity
            style={[styles.bookBtn, !canBook && !isAuthenticated && styles.bookBtnFull]}
            onPress={handleBook}
            activeOpacity={0.9}
          >
            <LinearGradient
              colors={canBook || !isAuthenticated ? [colors.baytgo, colors.baytgoDark] : [colors.slate500, colors.slate700]}
              style={styles.bookGradient}
            >
              <Ionicons name="calendar" size={18} color={colors.white} style={styles.bookIcon} />
              <Text style={styles.bookText}>
                {canBook ? 'Pesan Muthowif' : isAuthenticated ? 'Pilih Tanggal Dulu' : 'Masuk & Pesan'}
              </Text>
            </LinearGradient>
          </TouchableOpacity>
        </View>
      </View>

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
  container: { flex: 1, backgroundColor: colors.canvas },
  scroll: { paddingBottom: 110 },
  loadingSafe: { paddingHorizontal: 16, paddingBottom: 8 },
  loader: { marginTop: 40 },
  empty: { padding: 24, alignItems: 'center' },
  emptyText: { fontSize: 14, color: colors.slate500, fontWeight: '600', textAlign: 'center' },
  retry: { marginTop: 10, fontSize: 14, fontWeight: '800', color: colors.baytgo },
  backBtn: {
    width: 40,
    height: 40,
    borderRadius: 12,
    backgroundColor: colors.white,
    alignItems: 'center',
    justifyContent: 'center',
    borderWidth: 1,
    borderColor: colors.slate100,
  },
  heroWrap: {
    width: SCREEN_W,
    height: HERO_H,
    backgroundColor: colors.slate200,
  },
  heroImage: { width: '100%', height: '100%' },
  heroTopBar: { position: 'absolute', top: 0, left: 0, right: 0, paddingHorizontal: 16 },
  backBtnHero: {
    width: 42,
    height: 42,
    borderRadius: 14,
    backgroundColor: 'rgba(0,0,0,0.35)',
    alignItems: 'center',
    justifyContent: 'center',
    borderWidth: 1,
    borderColor: 'rgba(255,255,255,0.15)',
  },
  content: { paddingHorizontal: 16, marginTop: -16 },
  profileCard: {
    backgroundColor: colors.white,
    borderRadius: 20,
    padding: 18,
    marginBottom: 4,
    borderWidth: 1,
    borderColor: colors.slate100,
    shadowColor: '#000',
    shadowOffset: { width: 0, height: 4 },
    shadowOpacity: 0.06,
    shadowRadius: 12,
    elevation: 3,
  },
  profileTitleRow: { flexDirection: 'row', alignItems: 'center', flexWrap: 'wrap', gap: 8 },
  profileName: { fontSize: 22, fontWeight: '900', color: colors.slate900, flexShrink: 1 },
  verifiedChip: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 4,
    backgroundColor: colors.emerald50,
    paddingHorizontal: 8,
    paddingVertical: 4,
    borderRadius: 999,
    borderWidth: 1,
    borderColor: '#A7F3D0',
  },
  verifiedChipText: { fontSize: 10, fontWeight: '800', color: colors.emerald600, textTransform: 'uppercase' },
  newChip: {
    alignSelf: 'flex-start',
    marginTop: 8,
    backgroundColor: '#FEF3C7',
    paddingHorizontal: 10,
    paddingVertical: 4,
    borderRadius: 999,
  },
  newChipText: { fontSize: 11, fontWeight: '700', color: '#92400E' },
  profileMetaRow: { flexDirection: 'row', alignItems: 'center', gap: 6, marginTop: 10 },
  profileMetaText: { fontSize: 13, fontWeight: '600', color: colors.slate600, flex: 1 },
  locationBadge: {
    alignSelf: 'flex-start',
    marginTop: 10,
    paddingHorizontal: 12,
    paddingVertical: 8,
    borderRadius: 12,
    backgroundColor: '#E0F2FE',
    borderWidth: 1,
    borderColor: '#BAE6FD',
    gap: 4,
  },
  locationBadgeLabel: {
    fontSize: 10,
    fontWeight: '800',
    color: '#0369A1',
    textTransform: 'uppercase',
    letterSpacing: 0.6,
  },
  locationBadgeRow: { flexDirection: 'row', alignItems: 'center', gap: 6 },
  locationBadgeText: { flexShrink: 1, fontSize: 14, fontWeight: '800', color: '#0C4A6E' },
  profileRatingRow: { flexDirection: 'row', alignItems: 'center', gap: 8, marginTop: 8 },
  profileRatingText: { fontSize: 13, fontWeight: '700', color: colors.slate700 },
  profileRatingEmpty: { fontSize: 13, fontWeight: '600', color: colors.slate400 },
  statBar: {
    flexDirection: 'row',
    alignItems: 'stretch',
    marginTop: 16,
    paddingTop: 16,
    borderTopWidth: 1,
    borderTopColor: colors.slate100,
  },
  statCell: { flex: 1, alignItems: 'center', paddingHorizontal: 4 },
  statCellLabel: { marginTop: 6, fontSize: 10, fontWeight: '700', color: colors.slate500, textTransform: 'uppercase' },
  statCellValue: { marginTop: 4, fontSize: 12, fontWeight: '800', color: colors.slate900, textAlign: 'center', lineHeight: 16 },
  statDivider: { width: 1, backgroundColor: colors.slate100, marginVertical: 4 },
  langRow: { flexDirection: 'row', flexWrap: 'wrap', gap: 6, marginTop: 12 },
  langChip: {
    backgroundColor: colors.baytgoLight,
    borderRadius: 999,
    paddingHorizontal: 10,
    paddingVertical: 5,
  },
  langChipText: { fontSize: 12, fontWeight: '700', color: colors.baytgo },
  dateBanner: {
    flexDirection: 'row',
    alignItems: 'flex-start',
    gap: 10,
    backgroundColor: colors.emerald50,
    borderRadius: 14,
    padding: 12,
    marginTop: 14,
    borderWidth: 1,
    borderColor: '#A7F3D0',
  },
  dateBannerWarn: { backgroundColor: '#FFFBEB', borderColor: '#FDE68A' },
  dateBannerContent: { flex: 1 },
  dateBannerText: { fontSize: 13, fontWeight: '700', color: colors.baytgo },
  dateBannerOkText: { marginTop: 2, fontSize: 11, fontWeight: '600', color: colors.emerald600 },
  dateBannerWarnText: { marginTop: 2, fontSize: 11, fontWeight: '600', color: '#B45309' },
  sectionCard: {
    backgroundColor: colors.white,
    borderRadius: 22,
    padding: 16,
    marginTop: 14,
    borderWidth: 1,
    borderColor: colors.slate100,
    shadowColor: '#000',
    shadowOffset: { width: 0, height: 2 },
    shadowOpacity: 0.04,
    shadowRadius: 8,
    elevation: 2,
  },
  sectionHeader: { flexDirection: 'row', alignItems: 'flex-start', gap: 12, marginBottom: 14 },
  sectionIcon: {
    width: 40,
    height: 40,
    borderRadius: 12,
    alignItems: 'center',
    justifyContent: 'center',
  },
  sectionHeaderText: { flex: 1 },
  sectionTitle: { fontSize: 17, fontWeight: '900', color: colors.slate900 },
  sectionSubtitle: { marginTop: 3, fontSize: 12, color: colors.slate500, fontWeight: '500', lineHeight: 17 },
  packageCard: {
    borderRadius: 18,
    overflow: 'hidden',
    marginBottom: 12,
    borderWidth: 1,
    shadowColor: '#000',
    shadowOffset: { width: 0, height: 3 },
    shadowOpacity: 0.06,
    shadowRadius: 8,
    elevation: 3,
  },
  packageGroup: { borderColor: '#A7C4BC' },
  packagePrivate: { borderColor: colors.goldLight },
  packageGradient: { padding: 16 },
  packageTopRow: { flexDirection: 'row', flexWrap: 'wrap', alignItems: 'center', gap: 8, marginBottom: 12 },
  packageBadge: { borderRadius: 999, paddingHorizontal: 12, paddingVertical: 5 },
  packageTypeLabel: { fontSize: 10, fontWeight: '800', color: colors.white, textTransform: 'uppercase', letterSpacing: 0.8 },
  packageMiniBadge: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 4,
    backgroundColor: colors.white,
    borderRadius: 999,
    paddingHorizontal: 8,
    paddingVertical: 4,
    borderWidth: 1,
    borderColor: colors.slate100,
  },
  packageMiniText: { fontSize: 10, fontWeight: '700', color: colors.baytgo },
  packagePrice: { fontSize: 26, fontWeight: '900' },
  packagePerDay: { fontSize: 14, fontWeight: '600', color: colors.slate500 },
  packagePaxRow: { flexDirection: 'row', alignItems: 'center', gap: 6, marginTop: 6 },
  packagePax: { fontSize: 13, color: colors.slate600, fontWeight: '700' },
  packageDesc: { marginTop: 10, fontSize: 13, lineHeight: 21, color: colors.slate600, fontWeight: '500' },
  featureList: { marginTop: 12 },
  featureRow: { flexDirection: 'row', alignItems: 'flex-start', gap: 8, marginTop: 8 },
  featureText: { flex: 1, fontSize: 13, color: colors.slate700, fontWeight: '600', lineHeight: 19 },
  packageAddonBlock: {
    marginTop: 14,
    paddingTop: 12,
    borderTopWidth: 1,
    borderTopColor: 'rgba(0,0,0,0.06)',
  },
  packageAddonTitle: { fontSize: 10, fontWeight: '800', color: colors.slate500, textTransform: 'uppercase', marginBottom: 8 },
  packageAddonRow: { flexDirection: 'row', justifyContent: 'space-between', gap: 8, paddingVertical: 6 },
  packageAddonName: { flex: 1, fontSize: 12, fontWeight: '700', color: colors.slate800 },
  packageAddonPrice: { fontSize: 12, fontWeight: '800', color: colors.baytgo },
  addonList: { gap: 10 },
  addonRow: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 12,
    backgroundColor: colors.canvas,
    borderRadius: 16,
    padding: 12,
    borderWidth: 1,
    borderColor: colors.slate100,
  },
  addonIconWrap: {
    width: 44,
    height: 44,
    borderRadius: 14,
    alignItems: 'center',
    justifyContent: 'center',
  },
  addonRowBody: { flex: 1 },
  addonRowName: { fontSize: 14, fontWeight: '800', color: colors.slate900 },
  addonRowHint: { marginTop: 2, fontSize: 11, fontWeight: '600', color: colors.slate500 },
  addonPricePill: {
    backgroundColor: colors.baytgo,
    borderRadius: 999,
    paddingHorizontal: 10,
    paddingVertical: 6,
  },
  addonPricePillText: { fontSize: 11, fontWeight: '800', color: colors.white },
  addonEmpty: { alignItems: 'center', paddingVertical: 20, paddingHorizontal: 12, gap: 10 },
  addonEmptyText: { fontSize: 13, lineHeight: 20, color: colors.slate500, fontWeight: '600', textAlign: 'center' },
  bioText: { fontSize: 14, lineHeight: 22, color: colors.slate600, fontWeight: '500' },
  tagsLabel: { marginTop: 14, fontSize: 10, fontWeight: '800', color: colors.slate500, textTransform: 'uppercase', letterSpacing: 0.5 },
  tagsRow: { flexDirection: 'row', flexWrap: 'wrap', gap: 8, marginTop: 8 },
  tag: { backgroundColor: colors.baytgoLight, borderRadius: 999, paddingHorizontal: 10, paddingVertical: 5 },
  tagText: { fontSize: 11, fontWeight: '700', color: colors.baytgo },
  timelineBlock: { paddingLeft: 16, borderLeftWidth: 2, borderLeftColor: colors.baytgoLight },
  timelineBlockSpaced: { marginTop: 16 },
  timelineDot: {
    position: 'absolute',
    left: -5,
    top: 2,
    width: 8,
    height: 8,
    borderRadius: 4,
    backgroundColor: colors.baytgo,
  },
  timelineDotGold: { backgroundColor: colors.gold },
  timelineHeading: { fontSize: 11, fontWeight: '800', color: colors.baytgo, textTransform: 'uppercase', letterSpacing: 0.5 },
  timelineHeadingGold: { color: '#92400E' },
  timelineItem: { marginTop: 6, fontSize: 13, color: colors.slate700, lineHeight: 20, fontWeight: '500' },
  galleryList: { gap: 10 },
  galleryItem: {
    width: 140,
    height: 140,
    borderRadius: 14,
    overflow: 'hidden',
    backgroundColor: colors.slate100,
  },
  galleryImage: { width: '100%', height: '100%' },
  galleryOverlay: {
    position: 'absolute',
    left: 0,
    right: 0,
    bottom: 0,
    padding: 8,
    justifyContent: 'flex-end',
    minHeight: 60,
  },
  galleryTitle: { fontSize: 11, fontWeight: '700', color: colors.white },
  reviewSummary: {
    alignItems: 'center',
    backgroundColor: colors.slate100,
    borderRadius: 16,
    padding: 16,
    marginBottom: 14,
  },
  reviewSummaryScore: { fontSize: 36, fontWeight: '900', color: colors.slate900 },
  reviewSummaryCount: { marginTop: 4, fontSize: 12, color: colors.slate500, fontWeight: '600' },
  starsRow: { flexDirection: 'row', gap: 2, marginTop: 4 },
  reviewCard: {
    backgroundColor: colors.slate100,
    borderRadius: 14,
    padding: 14,
    marginBottom: 10,
  },
  reviewHeader: { flexDirection: 'row', gap: 10 },
  reviewAvatar: { width: 36, height: 36, borderRadius: 12, backgroundColor: colors.slate200 },
  reviewMeta: { flex: 1 },
  reviewName: { fontSize: 13, fontWeight: '800', color: colors.slate900 },
  reviewComment: { marginTop: 10, fontSize: 13, lineHeight: 20, color: colors.slate600, fontWeight: '500' },
  reviewTime: { marginTop: 8, fontSize: 11, color: colors.slate400, fontWeight: '600' },
  trustBar: {
    marginTop: 16,
    backgroundColor: colors.white,
    borderRadius: 16,
    padding: 14,
    gap: 10,
    borderWidth: 1,
    borderColor: colors.slate100,
  },
  trustItem: { flexDirection: 'row', alignItems: 'center', gap: 10 },
  trustText: { fontSize: 13, fontWeight: '600', color: colors.slate700 },
  blockedToggle: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 10,
    marginTop: 12,
    backgroundColor: '#FFFBEB',
    borderRadius: 14,
    padding: 14,
    borderWidth: 1,
    borderColor: '#FDE68A',
  },
  blockedToggleText: { flex: 1, fontSize: 13, fontWeight: '700', color: '#92400E' },
  blockedList: { marginTop: 8, gap: 6 },
  blockedItem: {
    backgroundColor: colors.white,
    borderRadius: 10,
    padding: 10,
    borderWidth: 1,
    borderColor: '#FDE68A',
  },
  blockedDate: { fontSize: 13, fontWeight: '800', color: colors.slate900 },
  blockedNote: { marginTop: 2, fontSize: 12, color: colors.slate600 },
  muted: { fontSize: 14, color: colors.slate500, fontWeight: '600' },
  footer: {
    position: 'absolute',
    left: 0,
    right: 0,
    bottom: 0,
  },
  footerFade: {
    position: 'absolute',
    left: 0,
    right: 0,
    top: -24,
    height: 24,
  },
  footerInner: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 12,
    padding: 16,
    paddingBottom: 24,
    backgroundColor: colors.canvas,
    borderTopWidth: 1,
    borderTopColor: colors.slate100,
  },
  footerPrice: { flex: 1 },
  footerPriceLabel: { fontSize: 11, fontWeight: '600', color: colors.slate500 },
  footerPriceValue: { fontSize: 16, fontWeight: '900', color: colors.baytgo },
  footerPriceDay: { fontSize: 12, fontWeight: '600', color: colors.slate500 },
  bookBtn: { flex: 1.2, borderRadius: 16, overflow: 'hidden' },
  bookBtnFull: { flex: 1 },
  bookGradient: { flexDirection: 'row', alignItems: 'center', justifyContent: 'center', paddingVertical: 14, gap: 8 },
  bookIcon: { marginRight: 2 },
  bookText: { color: colors.white, fontSize: 14, fontWeight: '800' },
  lightboxOverlay: {
    flex: 1,
    backgroundColor: 'rgba(15,23,42,0.95)',
    justifyContent: 'center',
    alignItems: 'center',
    padding: 16,
  },
  lightboxClose: {
    position: 'absolute',
    top: 48,
    right: 20,
    zIndex: 10,
    padding: 8,
  },
  lightboxNav: {
    position: 'absolute',
    top: '45%',
    zIndex: 10,
    padding: 12,
  },
  lightboxNavLeft: { left: 8 },
  lightboxNavRight: { right: 8 },
  lightboxImage: { width: SCREEN_W - 32, height: SCREEN_W * 0.85 },
  lightboxTitle: {
    marginTop: 16,
    fontSize: 15,
    fontWeight: '700',
    color: colors.white,
    textAlign: 'center',
  },
  lightboxCounter: { marginTop: 8, fontSize: 12, color: colors.slate400, fontWeight: '600' },
});
