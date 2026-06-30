import React from 'react';
import { View, Text, StyleSheet, TouchableOpacity } from 'react-native';
import { LinearGradient } from 'expo-linear-gradient';
import { Ionicons } from '@expo/vector-icons';
import TabPageHeader from '../components/TabPageHeader';
import { navigateRoot } from '../navigation/rootNavigation';
import { colors } from '../theme/colors';

export default function ProfileGuestScreen({ navigation }) {
  return (
    <View style={styles.container}>
      <TabPageHeader title="Profil" subtitle="Masuk untuk mengelola akun Anda" />
      <View style={styles.content}>
        <View style={styles.card}>
          <View style={styles.avatar}>
            <Ionicons name="person-outline" size={36} color={colors.gold} />
          </View>
          <Text style={styles.title}>Selamat datang di BaytGo</Text>
          <Text style={styles.sub}>Masuk atau daftar untuk pesan muthowif dan kelola booking.</Text>

          <TouchableOpacity
            style={styles.primaryBtn}
            onPress={() => navigateRoot(navigation, 'Login')}
            activeOpacity={0.9}
          >
            <LinearGradient colors={[colors.baytgo, colors.baytgoDark]} style={styles.primaryGradient}>
              <Text style={styles.primaryText}>Masuk</Text>
            </LinearGradient>
          </TouchableOpacity>

          <TouchableOpacity
            style={styles.secondaryBtn}
            onPress={() => navigateRoot(navigation, 'Register', { role: 'customer' })}
          >
            <Text style={styles.secondaryText}>Daftar Jamaah</Text>
          </TouchableOpacity>

          <TouchableOpacity onPress={() => navigateRoot(navigation, 'Register', { role: 'muthowif' })}>
            <Text style={styles.link}>Daftar sebagai Muthowif</Text>
          </TouchableOpacity>
        </View>
      </View>
    </View>
  );
}

const styles = StyleSheet.create({
  container: { flex: 1, backgroundColor: colors.canvas },
  content: { flex: 1, padding: 20 },
  card: {
    backgroundColor: colors.white,
    borderRadius: 24,
    padding: 24,
    alignItems: 'center',
    borderWidth: 1,
    borderColor: colors.slate100,
  },
  avatar: {
    width: 72,
    height: 72,
    borderRadius: 22,
    backgroundColor: colors.baytgo,
    alignItems: 'center',
    justifyContent: 'center',
    marginBottom: 16,
  },
  title: { fontSize: 20, fontWeight: '900', color: colors.baytgo, textAlign: 'center' },
  sub: { marginTop: 8, fontSize: 14, lineHeight: 20, color: colors.slate500, textAlign: 'center', fontWeight: '500' },
  primaryBtn: { marginTop: 24, width: '100%', borderRadius: 16, overflow: 'hidden' },
  primaryGradient: { paddingVertical: 16, alignItems: 'center' },
  primaryText: { color: colors.white, fontSize: 15, fontWeight: '800' },
  secondaryBtn: {
    marginTop: 10,
    width: '100%',
    borderRadius: 16,
    paddingVertical: 16,
    alignItems: 'center',
    borderWidth: 1,
    borderColor: colors.slate200,
  },
  secondaryText: { color: colors.baytgo, fontSize: 15, fontWeight: '800' },
  link: { marginTop: 14, fontSize: 13, fontWeight: '800', color: colors.goldMuted },
});
