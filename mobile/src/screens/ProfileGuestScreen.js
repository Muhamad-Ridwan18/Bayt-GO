import React from 'react';
import { View, Text, StyleSheet, TouchableOpacity, ScrollView } from 'react-native';
import { LinearGradient } from 'expo-linear-gradient';
import { Ionicons } from '@expo/vector-icons';
import TabPageHeader from '../components/TabPageHeader';
import { navigateRoot } from '../navigation/rootNavigation';
import { colors } from '../theme/colors';

const FEATURES = [
  { icon: 'search', title: 'Cari Muthowif', sub: 'Temukan pendamping ibadah terpercaya' },
  { icon: 'calendar', title: 'Kelola Booking', sub: 'Pantau pesanan dan jadwal perjalanan' },
  { icon: 'chatbubbles', title: 'Chat Langsung', sub: 'Komunikasi dengan muthowif sebelum pesan' },
];

export default function ProfileGuestScreen({ navigation }) {
  return (
    <View style={styles.container}>
      <TabPageHeader title="Profil" subtitle="Masuk untuk mengelola akun Anda" />

      <ScrollView contentContainerStyle={styles.scroll} showsVerticalScrollIndicator={false}>
        <View style={styles.heroCard}>
          <View style={styles.avatar}>
            <Ionicons name="person" size={40} color={colors.gold} />
          </View>
          <Text style={styles.title}>Selamat datang di BaytGo</Text>
          <Text style={styles.sub}>
            Masuk atau daftar untuk memesan muthowif dan mengelola perjalanan ibadah Anda.
          </Text>

          <TouchableOpacity
            style={styles.primaryBtn}
            onPress={() => navigateRoot(navigation, 'Login')}
            activeOpacity={0.9}
          >
            <LinearGradient colors={[colors.baytgo, colors.baytgoDark]} style={styles.primaryGradient}>
              <Ionicons name="log-in-outline" size={18} color={colors.white} />
              <Text style={styles.primaryText}>Masuk</Text>
            </LinearGradient>
          </TouchableOpacity>

          <TouchableOpacity
            style={styles.secondaryBtn}
            onPress={() => navigateRoot(navigation, 'Register', { role: 'customer' })}
            activeOpacity={0.88}
          >
            <Text style={styles.secondaryText}>Daftar sebagai Jamaah</Text>
          </TouchableOpacity>

          <TouchableOpacity onPress={() => navigateRoot(navigation, 'Register', { role: 'muthowif' })}>
            <Text style={styles.link}>Daftar sebagai Muthowif ›</Text>
          </TouchableOpacity>
        </View>

        <Text style={styles.sectionTitle}>Yang bisa Anda lakukan</Text>
        {FEATURES.map((feat) => (
          <View key={feat.title} style={styles.featureRow}>
            <View style={styles.featureIcon}>
              <Ionicons name={feat.icon} size={20} color={colors.baytgo} />
            </View>
            <View style={styles.featureCopy}>
              <Text style={styles.featureTitle}>{feat.title}</Text>
              <Text style={styles.featureSub}>{feat.sub}</Text>
            </View>
          </View>
        ))}
      </ScrollView>
    </View>
  );
}

const styles = StyleSheet.create({
  container: { flex: 1, backgroundColor: colors.canvas },
  scroll: { paddingHorizontal: 20, paddingTop: 16, paddingBottom: 32 },
  heroCard: {
    backgroundColor: colors.white,
    borderRadius: 22,
    padding: 24,
    alignItems: 'center',
    borderWidth: 1,
    borderColor: 'rgba(26,61,52,0.08)',
    shadowColor: '#0F2E28',
    shadowOffset: { width: 0, height: 4 },
    shadowOpacity: 0.06,
    shadowRadius: 14,
    elevation: 4,
    marginBottom: 24,
  },
  avatar: {
    width: 80,
    height: 80,
    borderRadius: 40,
    backgroundColor: colors.baytgo,
    alignItems: 'center',
    justifyContent: 'center',
    marginBottom: 16,
    borderWidth: 3,
    borderColor: colors.goldLight,
  },
  title: { fontSize: 20, fontWeight: '900', color: colors.baytgo, textAlign: 'center' },
  sub: {
    marginTop: 8,
    fontSize: 14,
    lineHeight: 21,
    color: colors.slate500,
    textAlign: 'center',
    fontWeight: '500',
  },
  primaryBtn: { marginTop: 22, width: '100%', borderRadius: 14, overflow: 'hidden' },
  primaryGradient: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'center',
    gap: 8,
    paddingVertical: 15,
  },
  primaryText: { color: colors.white, fontSize: 15, fontWeight: '800' },
  secondaryBtn: {
    marginTop: 10,
    width: '100%',
    borderRadius: 14,
    paddingVertical: 15,
    alignItems: 'center',
    borderWidth: 1.5,
    borderColor: colors.baytgo,
    backgroundColor: colors.white,
  },
  secondaryText: { color: colors.baytgo, fontSize: 15, fontWeight: '800' },
  link: { marginTop: 14, fontSize: 13, fontWeight: '800', color: colors.goldMuted },
  sectionTitle: {
    fontSize: 12,
    fontWeight: '800',
    color: colors.slate500,
    textTransform: 'uppercase',
    letterSpacing: 0.5,
    marginBottom: 12,
    marginLeft: 4,
  },
  featureRow: {
    flexDirection: 'row',
    alignItems: 'flex-start',
    gap: 12,
    backgroundColor: colors.white,
    borderRadius: 16,
    padding: 14,
    marginBottom: 10,
    borderWidth: 1,
    borderColor: 'rgba(26,61,52,0.08)',
  },
  featureIcon: {
    width: 40,
    height: 40,
    borderRadius: 12,
    backgroundColor: colors.baytgoLight,
    alignItems: 'center',
    justifyContent: 'center',
  },
  featureCopy: { flex: 1 },
  featureTitle: { fontSize: 14, fontWeight: '800', color: colors.slate900 },
  featureSub: { marginTop: 3, fontSize: 12, fontWeight: '600', color: colors.slate500, lineHeight: 17 },
});
