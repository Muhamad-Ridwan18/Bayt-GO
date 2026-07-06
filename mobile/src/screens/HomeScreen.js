import React, { useCallback, useEffect, useMemo, useState } from 'react';
import {
  View,
  Text,
  StyleSheet,
  ScrollView,
  TouchableOpacity,
  StatusBar,
  ActivityIndicator,
  RefreshControl,
  TextInput,
  Dimensions,
} from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';
import { LinearGradient } from 'expo-linear-gradient';
import { Ionicons } from '@expo/vector-icons';
import { colors } from '../theme/colors';
import { fetchHomeData } from '../api/home';
import { useAuth } from '../context/AuthContext';
import { useBrand } from '../context/BrandContext';
import { navigateRoot } from '../navigation/rootNavigation';
import AppLogo from '../components/AppLogo';
import MuthowifCard from '../components/MuthowifCard';
import GallerySection from '../components/GallerySection';
import DatePickerField, { parseIsoDate } from '../components/DatePickerField';

const { width: SCREEN_W } = Dimensions.get('window');

const TRUST_STATS = [
  { value: '2.500+', label: 'Muthowif terverifikasi', icon: 'shield-checkmark' },
  { value: '15.000+', label: 'Pesanan selesai', icon: 'checkmark-done' },
  { value: '98%', label: 'Kepuasan jamaah', icon: 'heart' },
  { value: '24/7', label: 'Customer support', icon: 'headset' },
];

const CATEGORIES = [
  { id: 'makkah', title: 'Muthowif Makkah', subtitle: 'Pendamping di Makkah', icon: 'location', bg: '#E0F2FE', color: '#0369A1', query: 'Makkah' },
  { id: 'madinah', title: 'Muthowif Madinah', subtitle: 'Pendamping di Madinah', icon: 'moon', bg: '#ECFDF5', color: '#059669', query: 'Madinah' },
  { id: 'id', title: 'Bahasa Indonesia', subtitle: 'Komunikasi nyaman', icon: 'chatbubbles', bg: '#FEF3C7', color: '#D97706', query: 'Indonesia' },
];

const STEPS = [
  { num: '1', title: 'Pilih tanggal', desc: 'Tentukan rentang perjalanan umrah Anda' },
  { num: '2', title: 'Lihat profil', desc: 'Harga, layanan, dan jadwal muthowif' },
  { num: '3', title: 'Pesan & bayar', desc: 'Booking aman dengan harga transparan' },
  { num: '4', title: 'Berangkat', desc: 'Muthowif siap mendampingi ibadah' },
];

function TrustStatCard({ item }) {
  return (
    <View style={styles.trustCard}>
      <View style={styles.trustIcon}>
        <Ionicons name={item.icon} size={18} color={colors.gold} />
      </View>
      <Text style={styles.trustValue}>{item.value}</Text>
      <Text style={styles.trustLabel}>{item.label}</Text>
    </View>
  );
}

function CategoryCard({ item, onPress }) {
  return (
    <TouchableOpacity style={styles.categoryCard} onPress={onPress} activeOpacity={0.9}>
      <View style={[styles.categoryIcon, { backgroundColor: item.bg }]}>
        <Ionicons name={item.icon} size={22} color={item.color} />
      </View>
      <Text style={styles.categoryTitle} numberOfLines={2}>{item.title}</Text>
      <Text style={styles.categorySub} numberOfLines={2}>{item.subtitle}</Text>
      <View style={styles.categoryLink}>
        <Text style={styles.categoryLinkText}>Jelajahi</Text>
        <Ionicons name="arrow-forward" size={12} color={colors.baytgo} />
      </View>
    </TouchableOpacity>
  );
}

function StepCard({ item }) {
  return (
    <View style={styles.stepCard}>
      <View style={styles.stepNum}>
        <Text style={styles.stepNumText}>{item.num}</Text>
      </View>
      <View style={styles.stepBody}>
        <Text style={styles.stepTitle}>{item.title}</Text>
        <Text style={styles.stepDesc}>{item.desc}</Text>
      </View>
    </View>
  );
}

export default function HomeScreen({ navigation }) {
  const { isAuthenticated, user } = useAuth();
  const { logoUrl, appName } = useBrand();
  const [loading, setLoading] = useState(true);
  const [refreshing, setRefreshing] = useState(false);
  const [muthowifs, setMuthowifs] = useState([]);
  const [gallery, setGallery] = useState([]);
  const [error, setError] = useState(null);
  const [startDate, setStartDate] = useState('');
  const [endDate, setEndDate] = useState('');
  const [searchName, setSearchName] = useState('');

  const today = useMemo(() => {
    const d = new Date();
    d.setHours(0, 0, 0, 0);
    return d;
  }, []);

  const handleStartDateChange = (iso) => {
    setStartDate(iso);
    if (endDate && iso && endDate < iso) setEndDate('');
  };

  const endMinDate = startDate ? parseIsoDate(startDate) : today;
  const endMaxDate = useMemo(() => {
    if (!startDate) return undefined;
    const max = parseIsoDate(startDate);
    max.setDate(max.getDate() + 90);
    return max;
  }, [startDate]);

  const openDirectory = (params = {}) => {
    navigation.navigate('Directory', {
      q: params.q ?? searchName.trim(),
      startDate: params.startDate ?? startDate.trim(),
      endDate: params.endDate ?? endDate.trim(),
    });
  };

  const openMuthowifDetail = (item) => {
    navigation.navigate('MuthowifDetail', {
      id: item.id,
      startDate: startDate.trim() || undefined,
      endDate: endDate.trim() || undefined,
    });
  };

  const loadData = useCallback(async (isRefresh = false) => {
    if (isRefresh) setRefreshing(true);
    else setLoading(true);

    try {
      const data = await fetchHomeData();
      setMuthowifs(data.featured_muthowifs || []);
      setGallery(data.gallery || []);
      setError(null);
    } catch (err) {
      setError(err.message || 'Gagal memuat data');
    } finally {
      setLoading(false);
      setRefreshing(false);
    }
  }, []);

  useEffect(() => {
    loadData();
  }, [loadData]);

  const handleAuthPress = () => {
    if (isAuthenticated) {
      navigation.getParent()?.navigate('ProfileTab');
    } else {
      navigateRoot(navigation, 'Login');
    }
  };

  return (
    <View style={styles.container}>
      <StatusBar barStyle="light-content" />

      <ScrollView
        showsVerticalScrollIndicator={false}
        bounces
        refreshControl={
          <RefreshControl refreshing={refreshing} onRefresh={() => loadData(true)} tintColor={colors.white} />
        }
      >
        <LinearGradient colors={['#0A221E', '#1A3D34', '#2D6A5A']} style={styles.hero}>
          <SafeAreaView edges={['top']}>
            <View style={styles.heroHeader}>
              <View style={styles.logoWrap}>
                <AppLogo url={logoUrl} name={appName} size={32} showName variant="light" />
              </View>
              <TouchableOpacity style={styles.authBtn} onPress={handleAuthPress}>
                <Ionicons name={isAuthenticated ? 'person' : 'log-in-outline'} size={20} color={colors.white} />
                <Text style={styles.authBtnText}>{isAuthenticated ? 'Profil' : 'Masuk'}</Text>
              </TouchableOpacity>
            </View>

            <View style={styles.heroBody}>
              <View style={styles.heroKicker}>
                <Ionicons name="checkmark-circle" size={14} color={colors.gold} />
                <Text style={styles.heroKickerText}>Marketplace Pendamping Umrah Terpercaya</Text>
              </View>
              <Text style={styles.heroTitle}>Pesan Muthowif{'\n'}semudah pesan hotel</Text>
              <Text style={styles.heroSub}>
                Pilih tanggal, lihat ketersediaan, dan pesan muthowif secara real-time.
              </Text>
              <View style={styles.heroChips}>
                <View style={styles.heroChip}>
                  <Ionicons name="cash-outline" size={13} color={colors.goldLight} />
                  <Text style={styles.heroChipText}>Harga transparan</Text>
                </View>
                <View style={styles.heroChip}>
                  <Ionicons name="calendar-outline" size={13} color={colors.goldLight} />
                  <Text style={styles.heroChipText}>Jadwal fleksibel</Text>
                </View>
                <View style={styles.heroChip}>
                  <Ionicons name="flash-outline" size={13} color={colors.goldLight} />
                  <Text style={styles.heroChipText}>Pesan instan</Text>
                </View>
              </View>
            </View>
          </SafeAreaView>
        </LinearGradient>

        <View style={styles.searchCard}>
          <View style={styles.searchCardHead}>
            <Ionicons name="search" size={20} color={colors.baytgo} />
            <Text style={styles.searchCardTitle}>Cari ketersediaan muthowif</Text>
          </View>
          <DatePickerField
            label="Tanggal mulai"
            value={startDate}
            onChange={handleStartDateChange}
            placeholder="Pilih tanggal mulai"
            minimumDate={today}
            variant="soft"
          />
          <DatePickerField
            label="Tanggal selesai"
            value={endDate}
            onChange={setEndDate}
            placeholder="Opsional — satu hari saja"
            minimumDate={endMinDate}
            maximumDate={endMaxDate}
            clearable
            onClear={() => setEndDate('')}
            variant="soft"
          />
          <View style={styles.searchField}>
            <Ionicons name="person-outline" size={18} color={colors.slate400} />
            <TextInput
              style={styles.searchInput}
              value={searchName}
              onChangeText={setSearchName}
              placeholder="Nama muthowif (opsional)"
              placeholderTextColor={colors.slate400}
              returnKeyType="search"
              onSubmitEditing={() => openDirectory()}
            />
          </View>
          <TouchableOpacity style={styles.searchBtn} onPress={() => openDirectory()} activeOpacity={0.9}>
            <LinearGradient colors={[colors.baytgo, colors.baytgoDark]} style={styles.searchBtnGradient}>
              <Ionicons name="compass" size={18} color={colors.white} />
              <Text style={styles.searchBtnText}>Cari Muthowif</Text>
            </LinearGradient>
          </TouchableOpacity>
        </View>

        <ScrollView
          horizontal
          showsHorizontalScrollIndicator={false}
          contentContainerStyle={styles.trustRow}
          nestedScrollEnabled
        >
          {TRUST_STATS.map((item) => (
            <TrustStatCard key={item.label} item={item} />
          ))}
        </ScrollView>

        <View style={styles.body}>
          <Text style={styles.sectionTitle}>Kategori pencarian</Text>
          <Text style={styles.sectionSub}>Temukan muthowif sesuai kebutuhan ibadah Anda</Text>
          <ScrollView
            horizontal
            showsHorizontalScrollIndicator={false}
            contentContainerStyle={styles.categoryRow}
            nestedScrollEnabled
          >
            {CATEGORIES.map((item) => (
              <CategoryCard
                key={item.id}
                item={item}
                onPress={() => openDirectory({ q: item.query })}
              />
            ))}
          </ScrollView>

          <View style={styles.sectionHead}>
            <View>
              <Text style={styles.sectionTitle}>Muthowif populer</Text>
              <Text style={styles.sectionSubInline}>Pilihan terbaik dari jamaah lain</Text>
            </View>
            <TouchableOpacity onPress={() => openDirectory()}>
              <Text style={styles.seeAll}>Lihat semua</Text>
            </TouchableOpacity>
          </View>

          {loading && !refreshing ? (
            <ActivityIndicator color={colors.baytgo} style={styles.loader} />
          ) : error ? (
            <View style={styles.emptyBox}>
              <Text style={styles.emptyText}>{error}</Text>
              <TouchableOpacity onPress={() => loadData()}>
                <Text style={styles.retryText}>Coba lagi</Text>
              </TouchableOpacity>
            </View>
          ) : muthowifs.length === 0 ? (
            <View style={styles.emptyBox}>
              <Ionicons name="people-outline" size={32} color={colors.slate400} />
              <Text style={styles.emptyText}>Muthowif terverifikasi akan muncul di sini.</Text>
            </View>
          ) : (
            <ScrollView
              horizontal
              showsHorizontalScrollIndicator={false}
              contentContainerStyle={styles.muthowifList}
              nestedScrollEnabled
            >
              {muthowifs.map((item) => (
                <MuthowifCard key={item.id} item={item} onPress={() => openMuthowifDetail(item)} />
              ))}
            </ScrollView>
          )}

          <GallerySection images={gallery} />

          <Text style={styles.sectionTitle}>Cara pakai marketplace</Text>
          <Text style={styles.sectionSub}>Empat langkah singkat dari pencarian sampai transaksi</Text>
          <View style={styles.stepsWrap}>
            {STEPS.map((item) => (
              <StepCard key={item.num} item={item} />
            ))}
          </View>

          {!isAuthenticated ? (
            <View style={styles.registerSection}>
              <Text style={styles.registerTitle}>Belum punya akun?</Text>
              <Text style={styles.registerSub}>Daftar gratis sebagai jamaah atau muthowif</Text>
              <View style={styles.registerRow}>
                <TouchableOpacity
                  style={styles.registerBtnPrimary}
                  onPress={() => navigateRoot(navigation, 'Register', { role: 'customer' })}
                  activeOpacity={0.9}
                >
                  <LinearGradient colors={[colors.baytgo, colors.baytgoDark]} style={styles.registerGradient}>
                    <Ionicons name="person-add" size={18} color={colors.white} />
                    <Text style={styles.registerBtnPrimaryText}>Daftar Jamaah</Text>
                  </LinearGradient>
                </TouchableOpacity>
                <TouchableOpacity
                  style={styles.registerBtnSecondary}
                  onPress={() => navigateRoot(navigation, 'Register', { role: 'muthowif' })}
                  activeOpacity={0.9}
                >
                  <Text style={styles.registerBtnSecondaryText}>Daftar Muthowif</Text>
                </TouchableOpacity>
              </View>
              <TouchableOpacity style={styles.loginLink} onPress={() => navigateRoot(navigation, 'Login')}>
                <Text style={styles.loginText}>Sudah punya akun? </Text>
                <Text style={styles.loginBold}>Masuk</Text>
              </TouchableOpacity>
            </View>
          ) : (
            <TouchableOpacity
              style={styles.welcomeBack}
              onPress={() => navigation.getParent()?.navigate('ProfileTab')}
              activeOpacity={0.9}
            >
              <LinearGradient colors={[colors.baytgo, colors.baytgoDark]} style={styles.welcomeBackGradient}>
                <View>
                  <Text style={styles.welcomeBackTitle}>Halo, {user?.name?.split(' ')[0] || 'Pengguna'}!</Text>
                  <Text style={styles.welcomeBackSub}>Kelola akun dan pesanan Anda</Text>
                </View>
                <Ionicons name="arrow-forward-circle" size={32} color={colors.gold} />
              </LinearGradient>
            </TouchableOpacity>
          )}

          <Text style={styles.footer}>Marketplace umrah — jamaah & muthowif</Text>
        </View>
      </ScrollView>
    </View>
  );
}

const styles = StyleSheet.create({
  container: { flex: 1, backgroundColor: colors.canvas },
  hero: {
    paddingBottom: 64,
    borderBottomLeftRadius: 32,
    borderBottomRightRadius: 32,
  },
  heroHeader: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
    paddingHorizontal: 20,
    paddingTop: 8,
    paddingBottom: 4,
  },
  logoWrap: { flex: 1 },
  authBtn: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 6,
    backgroundColor: 'rgba(255,255,255,0.12)',
    paddingHorizontal: 14,
    paddingVertical: 10,
    borderRadius: 14,
    borderWidth: 1,
    borderColor: 'rgba(255,255,255,0.18)',
  },
  authBtnText: { fontSize: 13, fontWeight: '800', color: colors.white },
  heroBody: { paddingHorizontal: 20, paddingTop: 16, paddingBottom: 8 },
  heroKicker: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 6,
    alignSelf: 'flex-start',
    backgroundColor: 'rgba(255,255,255,0.1)',
    paddingHorizontal: 12,
    paddingVertical: 6,
    borderRadius: 999,
    marginBottom: 14,
  },
  heroKickerText: { fontSize: 11, fontWeight: '800', color: colors.goldLight },
  heroTitle: {
    fontSize: 30,
    fontWeight: '900',
    lineHeight: 36,
    color: colors.white,
    letterSpacing: -0.8,
  },
  heroSub: {
    marginTop: 12,
    fontSize: 14,
    lineHeight: 21,
    color: 'rgba(255,255,255,0.72)',
    fontWeight: '500',
    maxWidth: SCREEN_W * 0.88,
  },
  heroChips: { flexDirection: 'row', flexWrap: 'wrap', gap: 8, marginTop: 18 },
  heroChip: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 5,
    backgroundColor: 'rgba(255,255,255,0.08)',
    paddingHorizontal: 10,
    paddingVertical: 6,
    borderRadius: 999,
    borderWidth: 1,
    borderColor: 'rgba(255,255,255,0.12)',
  },
  heroChipText: { fontSize: 11, fontWeight: '700', color: colors.goldLight },
  searchCard: {
    marginHorizontal: 20,
    marginTop: -44,
    backgroundColor: colors.white,
    borderRadius: 22,
    padding: 18,
    shadowColor: '#0F2E28',
    shadowOffset: { width: 0, height: 10 },
    shadowOpacity: 0.14,
    shadowRadius: 24,
    elevation: 10,
    borderWidth: 1,
    borderColor: colors.slate100,
  },
  searchCardHead: { flexDirection: 'row', alignItems: 'center', gap: 10, marginBottom: 14 },
  searchCardTitle: { fontSize: 16, fontWeight: '900', color: colors.baytgo },
  searchField: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 10,
    backgroundColor: colors.canvas,
    borderRadius: 14,
    paddingHorizontal: 14,
    paddingVertical: 4,
    marginBottom: 12,
    borderWidth: 1,
    borderColor: colors.slate100,
  },
  searchInput: { flex: 1, paddingVertical: 10, fontSize: 14, fontWeight: '600', color: colors.slate900 },
  searchBtn: { borderRadius: 14, overflow: 'hidden' },
  searchBtnGradient: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'center',
    gap: 8,
    paddingVertical: 15,
  },
  searchBtnText: { color: colors.white, fontSize: 15, fontWeight: '800' },
  trustRow: { paddingHorizontal: 20, gap: 10, paddingTop: 20, paddingBottom: 4 },
  trustCard: {
    width: SCREEN_W * 0.38,
    backgroundColor: colors.white,
    borderRadius: 16,
    padding: 14,
    borderWidth: 1,
    borderColor: colors.slate100,
    shadowColor: '#000',
    shadowOffset: { width: 0, height: 2 },
    shadowOpacity: 0.04,
    shadowRadius: 8,
    elevation: 2,
  },
  trustIcon: {
    width: 36,
    height: 36,
    borderRadius: 12,
    backgroundColor: colors.baytgoLight,
    alignItems: 'center',
    justifyContent: 'center',
    marginBottom: 10,
  },
  trustValue: { fontSize: 20, fontWeight: '900', color: colors.baytgo },
  trustLabel: { marginTop: 4, fontSize: 11, fontWeight: '600', color: colors.slate500, lineHeight: 15 },
  body: { paddingHorizontal: 20, paddingTop: 20, paddingBottom: 40 },
  sectionHead: { flexDirection: 'row', alignItems: 'flex-end', justifyContent: 'space-between', marginBottom: 14 },
  sectionTitle: { fontSize: 20, fontWeight: '900', color: colors.baytgo },
  sectionSub: { marginTop: 4, marginBottom: 14, fontSize: 13, fontWeight: '600', color: colors.slate500, lineHeight: 18 },
  sectionSubInline: { marginTop: 2, fontSize: 12, fontWeight: '600', color: colors.slate500 },
  seeAll: { fontSize: 13, fontWeight: '800', color: colors.goldMuted },
  categoryRow: { gap: 12, paddingBottom: 8, marginBottom: 24 },
  categoryCard: {
    width: SCREEN_W * 0.42,
    backgroundColor: colors.white,
    borderRadius: 18,
    padding: 16,
    borderWidth: 1,
    borderColor: colors.slate100,
    shadowColor: '#000',
    shadowOffset: { width: 0, height: 3 },
    shadowOpacity: 0.05,
    shadowRadius: 10,
    elevation: 2,
  },
  categoryIcon: {
    width: 48,
    height: 48,
    borderRadius: 16,
    alignItems: 'center',
    justifyContent: 'center',
    marginBottom: 12,
  },
  categoryTitle: { fontSize: 14, fontWeight: '900', color: colors.slate900, lineHeight: 18 },
  categorySub: { marginTop: 4, fontSize: 11, fontWeight: '600', color: colors.slate500, lineHeight: 15 },
  categoryLink: { flexDirection: 'row', alignItems: 'center', gap: 4, marginTop: 12 },
  categoryLinkText: { fontSize: 12, fontWeight: '800', color: colors.baytgo },
  muthowifList: { paddingRight: 4, paddingBottom: 8, marginBottom: 8 },
  loader: { marginBottom: 24 },
  emptyBox: {
    backgroundColor: colors.white,
    borderRadius: 20,
    borderWidth: 1,
    borderStyle: 'dashed',
    borderColor: colors.slate200,
    padding: 28,
    marginBottom: 24,
    alignItems: 'center',
    gap: 10,
  },
  emptyText: { fontSize: 14, color: colors.slate500, fontWeight: '600', textAlign: 'center', lineHeight: 20 },
  retryText: { fontSize: 14, fontWeight: '800', color: colors.baytgo },
  stepsWrap: { gap: 10, marginBottom: 28 },
  stepCard: {
    flexDirection: 'row',
    alignItems: 'flex-start',
    gap: 14,
    backgroundColor: colors.white,
    borderRadius: 16,
    padding: 14,
    borderWidth: 1,
    borderColor: colors.slate100,
  },
  stepNum: {
    width: 32,
    height: 32,
    borderRadius: 10,
    backgroundColor: colors.baytgo,
    alignItems: 'center',
    justifyContent: 'center',
  },
  stepNumText: { fontSize: 14, fontWeight: '900', color: colors.gold },
  stepBody: { flex: 1 },
  stepTitle: { fontSize: 14, fontWeight: '900', color: colors.slate900 },
  stepDesc: { marginTop: 3, fontSize: 12, fontWeight: '600', color: colors.slate500, lineHeight: 17 },
  registerSection: {
    backgroundColor: colors.white,
    borderRadius: 22,
    padding: 20,
    marginBottom: 16,
    borderWidth: 1,
    borderColor: colors.slate100,
  },
  registerTitle: { fontSize: 18, fontWeight: '900', color: colors.baytgo },
  registerSub: { marginTop: 6, fontSize: 13, fontWeight: '600', color: colors.slate500, marginBottom: 16 },
  registerRow: { gap: 10 },
  registerBtnPrimary: { borderRadius: 14, overflow: 'hidden' },
  registerGradient: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'center',
    gap: 8,
    paddingVertical: 15,
  },
  registerBtnPrimaryText: { fontSize: 15, fontWeight: '800', color: colors.white },
  registerBtnSecondary: {
    borderRadius: 14,
    paddingVertical: 14,
    alignItems: 'center',
    borderWidth: 2,
    borderColor: colors.baytgo,
  },
  registerBtnSecondaryText: { fontSize: 14, fontWeight: '800', color: colors.baytgo },
  loginLink: { flexDirection: 'row', justifyContent: 'center', marginTop: 16 },
  loginText: { fontSize: 14, color: colors.slate500, fontWeight: '600' },
  loginBold: { fontSize: 14, color: colors.baytgo, fontWeight: '800' },
  welcomeBack: { borderRadius: 20, overflow: 'hidden', marginBottom: 16 },
  welcomeBackGradient: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
    padding: 20,
  },
  welcomeBackTitle: { fontSize: 18, fontWeight: '900', color: colors.white },
  welcomeBackSub: { marginTop: 4, fontSize: 13, fontWeight: '600', color: colors.goldLight },
  footer: {
    textAlign: 'center',
    fontSize: 12,
    color: colors.slate400,
    fontWeight: '600',
    marginTop: 8,
  },
});
