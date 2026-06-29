import React from 'react';
import { View, Text, StyleSheet, TouchableOpacity } from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';
import { LinearGradient } from 'expo-linear-gradient';
import { Ionicons } from '@expo/vector-icons';
import { useAuth } from '../context/AuthContext';
import { colors } from '../theme/colors';

export default function DashboardScreen({ navigation }) {
  const { user, logout } = useAuth();

  const handleLogout = async () => {
    await logout();
    navigation.reset({ index: 0, routes: [{ name: 'Home' }] });
  };

  const isMuthowif = user?.role === 'muthowif';

  return (
    <SafeAreaView style={styles.safe} edges={['top', 'bottom']}>
      <LinearGradient colors={[colors.canvas, colors.white]} style={StyleSheet.absoluteFill} />

      <View style={styles.header}>
        <View>
          <Text style={styles.greeting}>Halo,</Text>
          <Text style={styles.name}>{user?.name || 'Pengguna'}</Text>
        </View>
        <TouchableOpacity style={styles.logoutBtn} onPress={handleLogout}>
          <Ionicons name="log-out-outline" size={20} color={colors.baytgo} />
        </TouchableOpacity>
      </View>

      <View style={styles.card}>
        <View style={styles.iconWrap}>
          <Ionicons name={isMuthowif ? 'briefcase' : 'person'} size={28} color={colors.gold} />
        </View>
        <Text style={styles.cardTitle}>
          {isMuthowif ? 'Dashboard Muthowif' : 'Dashboard Jamaah'}
        </Text>
        <Text style={styles.cardSub}>
          {isMuthowif
            ? 'Kelola jadwal, booking, dan layanan Anda dari sini.'
            : 'Cari muthowif, kelola booking, dan pembayaran dari sini.'}
        </Text>
        <Text style={styles.email}>{user?.email}</Text>
      </View>

      <TouchableOpacity style={styles.homeBtn} onPress={() => navigation.navigate('Home')}>
        <Text style={styles.homeBtnText}>Kembali ke Beranda</Text>
      </TouchableOpacity>
    </SafeAreaView>
  );
}

const styles = StyleSheet.create({
  safe: { flex: 1, backgroundColor: colors.canvas, padding: 20 },
  header: { flexDirection: 'row', justifyContent: 'space-between', alignItems: 'center', marginBottom: 24 },
  greeting: { fontSize: 14, color: colors.slate500, fontWeight: '600' },
  name: { fontSize: 24, fontWeight: '900', color: colors.baytgo, marginTop: 2 },
  logoutBtn: {
    width: 44,
    height: 44,
    borderRadius: 14,
    backgroundColor: colors.white,
    alignItems: 'center',
    justifyContent: 'center',
    borderWidth: 1,
    borderColor: colors.slate100,
  },
  card: {
    backgroundColor: colors.white,
    borderRadius: 24,
    padding: 24,
    borderWidth: 1,
    borderColor: colors.slate100,
    alignItems: 'center',
  },
  iconWrap: {
    width: 64,
    height: 64,
    borderRadius: 20,
    backgroundColor: colors.baytgo,
    alignItems: 'center',
    justifyContent: 'center',
    marginBottom: 16,
  },
  cardTitle: { fontSize: 20, fontWeight: '900', color: colors.baytgo, marginBottom: 8 },
  cardSub: { fontSize: 14, color: colors.slate500, textAlign: 'center', lineHeight: 20, fontWeight: '500' },
  email: { marginTop: 16, fontSize: 13, color: colors.slate400, fontWeight: '600' },
  homeBtn: {
    marginTop: 20,
    backgroundColor: colors.baytgo,
    borderRadius: 16,
    paddingVertical: 16,
    alignItems: 'center',
  },
  homeBtnText: { color: colors.white, fontWeight: '800', fontSize: 15 },
});
