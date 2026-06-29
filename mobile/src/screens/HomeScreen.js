import React, { useCallback, useEffect, useState } from 'react';
import {
  View,
  Text,
  StyleSheet,
  ScrollView,
  TouchableOpacity,
  StatusBar,
  Alert,
  ActivityIndicator,
  RefreshControl,
} from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';
import { LinearGradient } from 'expo-linear-gradient';
import { Ionicons } from '@expo/vector-icons';
import { colors } from '../theme/colors';
import { fetchHomeData } from '../api/home';
import { useAuth } from '../context/AuthContext';
import MuthowifCard from '../components/MuthowifCard';
import GallerySection from '../components/GallerySection';

const FEATURES = [
  { icon: 'cash-outline', title: 'Harga transparan' },
  { icon: 'calendar-outline', title: 'Jadwal fleksibel' },
  { icon: 'flash-outline', title: 'Pesan instan' },
];

export default function HomeScreen({ navigation }) {
  const { isAuthenticated, user } = useAuth();
  const [loading, setLoading] = useState(true);
  const [refreshing, setRefreshing] = useState(false);
  const [muthowifs, setMuthowifs] = useState([]);
  const [gallery, setGallery] = useState([]);
  const [error, setError] = useState(null);

  const comingSoon = (label) => Alert.alert('Segera hadir', `${label} akan tersedia di versi berikutnya.`);

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

  return (
    <View style={styles.container}>
      <StatusBar barStyle="dark-content" />
      <LinearGradient
        colors={[colors.canvas, colors.canvasSoft, colors.white]}
        style={StyleSheet.absoluteFill}
      />

      <SafeAreaView style={styles.safe} edges={['top']}>
        <View style={styles.header}>
          <View style={styles.logoRow}>
            <View style={styles.logoMark}>
              <Text style={styles.logoMarkText}>B</Text>
            </View>
            <Text style={styles.logoText}>BaytGo</Text>
          </View>
          <TouchableOpacity
            style={styles.menuBtn}
            onPress={() => (isAuthenticated ? navigation.navigate('Dashboard') : navigation.navigate('Login'))}
          >
            <Ionicons name={isAuthenticated ? 'person' : 'log-in-outline'} size={22} color={colors.baytgo} />
          </TouchableOpacity>
        </View>

        <ScrollView
          showsVerticalScrollIndicator={false}
          contentContainerStyle={styles.scroll}
          refreshControl={
            <RefreshControl refreshing={refreshing} onRefresh={() => loadData(true)} tintColor={colors.baytgo} />
          }
        >
          <View style={styles.hero}>
            <View style={styles.kicker}>
              <Ionicons name="checkmark-circle" size={16} color={colors.emerald600} />
              <Text style={styles.kickerText}>Marketplace Pendamping Umrah Terpercaya</Text>
            </View>

            <Text style={styles.heroTitle}>Pesan Muthowif semudah pesan hotel</Text>
            <Text style={styles.heroSub}>
              Pilih tanggal, lihat ketersediaan, dan pesan muthowif secara real-time dengan harga transparan.
            </Text>

            <View style={styles.featureRow}>
              {FEATURES.map((item) => (
                <View key={item.title} style={styles.featureChip}>
                  <Ionicons name={item.icon} size={14} color={colors.baytgo} />
                  <Text style={styles.featureChipText}>{item.title}</Text>
                </View>
              ))}
            </View>
          </View>

          <View style={styles.searchCard}>
            <Text style={styles.searchLabel}>Cari ketersediaan muthowif</Text>
            <TouchableOpacity style={styles.searchField} onPress={() => comingSoon('Pencarian')}>
              <Ionicons name="calendar-outline" size={20} color={colors.slate400} />
              <Text style={styles.searchPlaceholder}>Tanggal mulai perjalanan</Text>
            </TouchableOpacity>
            <TouchableOpacity style={styles.searchField} onPress={() => comingSoon('Pencarian')}>
              <Ionicons name="search-outline" size={20} color={colors.slate400} />
              <Text style={styles.searchPlaceholder}>Nama muthowif (opsional)</Text>
            </TouchableOpacity>
            <TouchableOpacity
              style={styles.searchBtn}
              onPress={() => comingSoon('Pencarian')}
              activeOpacity={0.9}
            >
              <LinearGradient
                colors={[colors.baytgo, colors.baytgoDark]}
                style={styles.searchBtnGradient}
              >
                <Ionicons name="search" size={18} color={colors.white} />
                <Text style={styles.searchBtnText}>Cari Muthowif</Text>
              </LinearGradient>
            </TouchableOpacity>
            <Text style={styles.searchTip}>
              Tips: Tanggal selesai boleh dikosongkan jika hanya satu hari.
            </Text>
          </View>

          <View style={styles.sectionHeader}>
            <Text style={styles.sectionTitle}>Muthowif populer</Text>
            <TouchableOpacity onPress={() => comingSoon('Direktori')}>
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
              <Text style={styles.emptyText}>Muthowif terverifikasi akan muncul di sini begitu tersedia.</Text>
            </View>
          ) : (
            <ScrollView
              horizontal
              showsHorizontalScrollIndicator={false}
              contentContainerStyle={styles.muthowifList}
              nestedScrollEnabled
            >
              {muthowifs.map((item) => (
                <MuthowifCard
                  key={item.id}
                  item={item}
                  onPress={() => comingSoon('Detail muthowif')}
                />
              ))}
            </ScrollView>
          )}

          <GallerySection images={gallery} />

          <View style={styles.ctaCard}>
            <LinearGradient
              colors={[colors.baytgo, colors.baytgoDark]}
              start={{ x: 0, y: 0 }}
              end={{ x: 1, y: 1 }}
              style={styles.ctaGradient}
            >
              {isAuthenticated ? (
                <>
                  <Text style={styles.ctaTitle}>Halo, {user?.name?.split(' ')[0] || 'Pengguna'}!</Text>
                  <Text style={styles.ctaSub}>Buka dashboard untuk mengelola akun Anda.</Text>
                  <TouchableOpacity
                    style={styles.ctaPrimary}
                    onPress={() => navigation.navigate('Dashboard')}
                  >
                    <Text style={styles.ctaPrimaryText}>Buka Dashboard</Text>
                  </TouchableOpacity>
                </>
              ) : (
                <>
                  <Text style={styles.ctaTitle}>Belum punya akun?</Text>
                  <Text style={styles.ctaSub}>
                    Daftar sekarang sebagai jamaah atau muthowif — gratis.
                  </Text>
                  <View style={styles.ctaBtns}>
                    <TouchableOpacity
                      style={styles.ctaPrimary}
                      onPress={() => navigation.navigate('Register', { role: 'customer' })}
                    >
                      <Text style={styles.ctaPrimaryText}>Daftar Jamaah</Text>
                    </TouchableOpacity>
                    <TouchableOpacity
                      style={styles.ctaSecondary}
                      onPress={() => navigation.navigate('Register', { role: 'muthowif' })}
                    >
                      <Text style={styles.ctaSecondaryText}>Daftar Muthowif</Text>
                    </TouchableOpacity>
                  </View>
                </>
              )}
            </LinearGradient>
          </View>

          {!isAuthenticated && (
            <TouchableOpacity style={styles.loginLink} onPress={() => navigation.navigate('Login')}>
              <Text style={styles.loginText}>Sudah punya akun? </Text>
              <Text style={styles.loginBold}>Masuk</Text>
            </TouchableOpacity>
          )}

          <Text style={styles.footer}>Marketplace umrah — jamaah & muthowif</Text>
        </ScrollView>
      </SafeAreaView>
    </View>
  );
}

const styles = StyleSheet.create({
  container: { flex: 1, backgroundColor: colors.canvas },
  safe: { flex: 1 },
  header: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
    paddingHorizontal: 20,
    paddingVertical: 12,
  },
  logoRow: { flexDirection: 'row', alignItems: 'center', gap: 10 },
  logoMark: {
    width: 36,
    height: 36,
    borderRadius: 12,
    backgroundColor: colors.baytgo,
    alignItems: 'center',
    justifyContent: 'center',
  },
  logoMarkText: { color: colors.gold, fontSize: 18, fontWeight: '900' },
  logoText: { fontSize: 20, fontWeight: '800', color: colors.baytgo, letterSpacing: -0.5 },
  menuBtn: {
    width: 44,
    height: 44,
    borderRadius: 14,
    backgroundColor: colors.white,
    alignItems: 'center',
    justifyContent: 'center',
    borderWidth: 1,
    borderColor: colors.slate100,
  },
  scroll: { paddingHorizontal: 20, paddingBottom: 40 },
  hero: { marginTop: 8, marginBottom: 24 },
  kicker: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 8,
    alignSelf: 'flex-start',
    backgroundColor: colors.emerald50,
    paddingHorizontal: 12,
    paddingVertical: 8,
    borderRadius: 999,
    marginBottom: 16,
  },
  kickerText: { fontSize: 11, fontWeight: '800', color: colors.baytgo, flexShrink: 1 },
  heroTitle: {
    fontSize: 28,
    fontWeight: '900',
    lineHeight: 34,
    color: colors.baytgo,
    letterSpacing: -0.5,
    marginBottom: 12,
  },
  heroSub: {
    fontSize: 15,
    lineHeight: 22,
    color: colors.slate600,
    fontWeight: '500',
  },
  featureRow: { flexDirection: 'row', flexWrap: 'wrap', gap: 8, marginTop: 20 },
  featureChip: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 6,
    backgroundColor: colors.goldLight + '55',
    paddingHorizontal: 12,
    paddingVertical: 8,
    borderRadius: 999,
    borderWidth: 1,
    borderColor: colors.gold + '33',
  },
  featureChipText: { fontSize: 12, fontWeight: '700', color: colors.baytgo },
  searchCard: {
    backgroundColor: colors.white,
    borderRadius: 24,
    padding: 20,
    marginBottom: 28,
    borderWidth: 1,
    borderColor: colors.slate100,
    shadowColor: colors.baytgo,
    shadowOffset: { width: 0, height: 6 },
    shadowOpacity: 0.08,
    shadowRadius: 16,
    elevation: 4,
  },
  searchLabel: {
    fontSize: 13,
    fontWeight: '800',
    color: colors.slate700,
    marginBottom: 14,
    textTransform: 'uppercase',
    letterSpacing: 0.5,
  },
  searchField: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 12,
    backgroundColor: colors.canvas,
    borderRadius: 16,
    paddingHorizontal: 16,
    paddingVertical: 14,
    marginBottom: 10,
    borderWidth: 1,
    borderColor: colors.slate100,
  },
  searchPlaceholder: { fontSize: 14, fontWeight: '600', color: colors.slate400, flex: 1 },
  searchBtn: { borderRadius: 16, overflow: 'hidden', marginTop: 6 },
  searchBtnGradient: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'center',
    gap: 8,
    paddingVertical: 16,
  },
  searchBtnText: { color: colors.white, fontSize: 15, fontWeight: '800' },
  searchTip: {
    marginTop: 12,
    fontSize: 12,
    color: colors.slate500,
    fontWeight: '600',
    lineHeight: 18,
  },
  sectionHeader: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
    marginBottom: 16,
  },
  sectionTitle: { fontSize: 22, fontWeight: '900', color: colors.baytgo },
  seeAll: { fontSize: 13, fontWeight: '800', color: colors.goldMuted },
  muthowifList: { paddingRight: 4, paddingBottom: 8 },
  loader: { marginBottom: 24 },
  emptyBox: {
    backgroundColor: colors.white,
    borderRadius: 20,
    borderWidth: 1,
    borderStyle: 'dashed',
    borderColor: colors.slate200,
    padding: 24,
    marginBottom: 24,
    alignItems: 'center',
  },
  emptyText: { fontSize: 14, color: colors.slate500, fontWeight: '600', textAlign: 'center', lineHeight: 20 },
  retryText: { marginTop: 10, fontSize: 14, fontWeight: '800', color: colors.baytgo },
  ctaCard: { borderRadius: 24, overflow: 'hidden', marginBottom: 16 },
  ctaGradient: { padding: 24 },
  ctaTitle: { fontSize: 20, fontWeight: '900', color: colors.white, marginBottom: 8 },
  ctaSub: { fontSize: 14, lineHeight: 20, color: colors.goldLight, fontWeight: '500', marginBottom: 20 },
  ctaBtns: { gap: 10 },
  ctaPrimary: {
    backgroundColor: colors.gold,
    borderRadius: 14,
    paddingVertical: 14,
    alignItems: 'center',
  },
  ctaPrimaryText: { fontSize: 14, fontWeight: '800', color: colors.baytgoDark },
  ctaSecondary: {
    borderRadius: 14,
    paddingVertical: 14,
    alignItems: 'center',
    borderWidth: 1,
    borderColor: 'rgba(255,255,255,0.35)',
  },
  ctaSecondaryText: { fontSize: 14, fontWeight: '800', color: colors.white },
  loginLink: { flexDirection: 'row', justifyContent: 'center', marginBottom: 20 },
  loginText: { fontSize: 14, color: colors.slate500, fontWeight: '600' },
  loginBold: { fontSize: 14, color: colors.baytgo, fontWeight: '800' },
  footer: {
    textAlign: 'center',
    fontSize: 12,
    color: colors.slate400,
    fontWeight: '600',
    marginBottom: 8,
  },
});
