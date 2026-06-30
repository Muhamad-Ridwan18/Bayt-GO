import React from 'react';
import { View, Text, StyleSheet, TouchableOpacity } from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';
import { LinearGradient } from 'expo-linear-gradient';
import { Ionicons } from '@expo/vector-icons';
import { navigateRoot } from '../navigation/rootNavigation';
import { colors } from '../theme/colors';

export default function AuthGateScreen({ navigation, title, message }) {
  return (
    <SafeAreaView style={styles.safe} edges={['top', 'bottom']}>
      <LinearGradient colors={[colors.canvas, colors.white]} style={StyleSheet.absoluteFill} />
      <View style={styles.content}>
        <View style={styles.iconWrap}>
          <Ionicons name="lock-closed-outline" size={32} color={colors.gold} />
        </View>
        <Text style={styles.title}>{title}</Text>
        <Text style={styles.message}>{message}</Text>
        <TouchableOpacity
          style={styles.primaryBtn}
          onPress={() => navigateRoot(navigation, 'Login')}
          activeOpacity={0.9}
        >
          <LinearGradient colors={[colors.baytgo, colors.baytgoDark]} style={styles.primaryGradient}>
            <Text style={styles.primaryText}>Masuk</Text>
          </LinearGradient>
        </TouchableOpacity>
        <TouchableOpacity onPress={() => navigateRoot(navigation, 'Register', { role: 'customer' })}>
          <Text style={styles.link}>Belum punya akun? Daftar</Text>
        </TouchableOpacity>
      </View>
    </SafeAreaView>
  );
}

const styles = StyleSheet.create({
  safe: { flex: 1, backgroundColor: colors.canvas },
  content: { flex: 1, alignItems: 'center', justifyContent: 'center', padding: 24 },
  iconWrap: {
    width: 72,
    height: 72,
    borderRadius: 22,
    backgroundColor: colors.baytgo,
    alignItems: 'center',
    justifyContent: 'center',
    marginBottom: 20,
  },
  title: { fontSize: 22, fontWeight: '900', color: colors.baytgo, textAlign: 'center' },
  message: {
    marginTop: 10,
    fontSize: 14,
    lineHeight: 21,
    color: colors.slate500,
    fontWeight: '500',
    textAlign: 'center',
    maxWidth: 300,
  },
  primaryBtn: { marginTop: 24, width: '100%', maxWidth: 280, borderRadius: 16, overflow: 'hidden' },
  primaryGradient: { paddingVertical: 16, alignItems: 'center' },
  primaryText: { color: colors.white, fontSize: 15, fontWeight: '800' },
  link: { marginTop: 16, fontSize: 14, fontWeight: '800', color: colors.baytgo },
});
