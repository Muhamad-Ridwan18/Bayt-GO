import React, { useCallback, useState } from 'react';
import {
  View,
  Text,
  StyleSheet,
  ScrollView,
  TouchableOpacity,
  ActivityIndicator,
  RefreshControl,
} from 'react-native';
import { useFocusEffect } from '@react-navigation/native';
import { Ionicons } from '@expo/vector-icons';
import TabPageHeader from '../components/TabPageHeader';
import { useAuth } from '../context/AuthContext';
import { fetchProfile } from '../api/profile';
import { fetchCustomerDashboard, fetchMuthowifDashboard } from '../api/dashboard';
import { resetRoot } from '../navigation/rootNavigation';
import { colors } from '../theme/colors';

function StatCard({ stat }) {
  return (
    <View style={[styles.statCard, { borderLeftColor: stat.color || colors.baytgo }]}>
      <Text style={styles.statValue}>{stat.value}</Text>
      <Text style={styles.statLabel}>{stat.label}</Text>
    </View>
  );
}

export default function ProfileScreen({ navigation }) {
  const { user, token, logout } = useAuth();
  const isMuthowif = user?.role === 'muthowif';

  const [loading, setLoading] = useState(true);
  const [refreshing, setRefreshing] = useState(false);
  const [profile, setProfile] = useState(null);
  const [stats, setStats] = useState([]);

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
    } catch {
      setProfile(null);
      setStats([]);
    } finally {
      setLoading(false);
      setRefreshing(false);
    }
  }, [token, isMuthowif]);

  useFocusEffect(
    useCallback(() => {
      load();
    }, [load]),
  );

  const handleLogout = async () => {
    await logout();
    resetRoot(navigation, [{ name: 'Main' }]);
  };

  const roleLabel = isMuthowif ? 'Muthowif' : 'Jamaah';

  return (
    <View style={styles.container}>
      <TabPageHeader title="Profil" subtitle={user?.name} />

      {loading && !refreshing ? (
        <ActivityIndicator color={colors.baytgo} style={styles.loader} />
      ) : (
        <ScrollView
          contentContainerStyle={styles.scroll}
          refreshControl={
            <RefreshControl refreshing={refreshing} onRefresh={() => load(true)} tintColor={colors.baytgo} />
          }
        >
          <View style={styles.card}>
            <View style={styles.avatar}>
              <Text style={styles.avatarText}>{user?.name?.charAt(0)?.toUpperCase() || 'U'}</Text>
            </View>
            <Text style={styles.name}>{user?.name}</Text>
            <Text style={styles.email}>{user?.email}</Text>
            <View style={styles.roleBadge}>
              <Text style={styles.roleText}>{roleLabel}</Text>
            </View>
          </View>

          {stats.length > 0 ? (
            <View style={styles.statsGrid}>
              {stats.map((stat) => (
                <StatCard key={stat.label} stat={stat} />
              ))}
            </View>
          ) : null}

          {profile?.muthowif?.phone ? (
            <View style={styles.infoCard}>
              <Text style={styles.infoLabel}>Telepon</Text>
              <Text style={styles.infoValue}>{profile.muthowif.phone}</Text>
            </View>
          ) : null}

          {!isMuthowif ? (
            <TouchableOpacity
              style={styles.menuBtn}
              onPress={() => navigation.getParent()?.navigate('BookingsTab', { screen: 'BookingsList' })}
            >
              <Ionicons name="receipt-outline" size={20} color={colors.baytgo} />
              <Text style={styles.menuText}>Pesanan Saya</Text>
              <Ionicons name="chevron-forward" size={18} color={colors.slate400} />
            </TouchableOpacity>
          ) : null}

          <TouchableOpacity
            style={styles.menuBtn}
            onPress={() => navigation.navigate('EditProfile', { profile })}
          >
            <Ionicons name="create-outline" size={20} color={colors.baytgo} />
            <Text style={styles.menuText}>Edit profil</Text>
            <Ionicons name="chevron-forward" size={18} color={colors.slate400} />
          </TouchableOpacity>

          <TouchableOpacity
            style={styles.menuBtn}
            onPress={() => navigation.navigate('ChangePassword')}
          >
            <Ionicons name="key-outline" size={20} color={colors.baytgo} />
            <Text style={styles.menuText}>Ganti password</Text>
            <Ionicons name="chevron-forward" size={18} color={colors.slate400} />
          </TouchableOpacity>

          <TouchableOpacity style={styles.logoutBtn} onPress={handleLogout}>
            <Ionicons name="log-out-outline" size={20} color="#B91C1C" />
            <Text style={styles.logoutText}>Keluar</Text>
          </TouchableOpacity>
        </ScrollView>
      )}
    </View>
  );
}

const styles = StyleSheet.create({
  container: { flex: 1, backgroundColor: colors.canvas },
  scroll: { padding: 20, paddingBottom: 32 },
  loader: { marginTop: 40 },
  card: {
    backgroundColor: colors.white,
    borderRadius: 24,
    padding: 24,
    alignItems: 'center',
    borderWidth: 1,
    borderColor: colors.slate100,
    marginBottom: 16,
  },
  avatar: {
    width: 72,
    height: 72,
    borderRadius: 22,
    backgroundColor: colors.baytgo,
    alignItems: 'center',
    justifyContent: 'center',
    marginBottom: 12,
  },
  avatarText: { fontSize: 28, fontWeight: '900', color: colors.gold },
  name: { fontSize: 22, fontWeight: '900', color: colors.baytgo },
  email: { marginTop: 4, fontSize: 14, fontWeight: '600', color: colors.slate500 },
  roleBadge: {
    marginTop: 12,
    backgroundColor: colors.emerald50,
    borderRadius: 999,
    paddingHorizontal: 12,
    paddingVertical: 6,
  },
  roleText: { fontSize: 11, fontWeight: '800', color: colors.baytgo },
  statsGrid: { flexDirection: 'row', flexWrap: 'wrap', gap: 10, marginBottom: 16 },
  statCard: {
    flex: 1,
    minWidth: '30%',
    backgroundColor: colors.white,
    borderRadius: 16,
    padding: 14,
    borderWidth: 1,
    borderColor: colors.slate100,
    borderLeftWidth: 4,
  },
  statValue: { fontSize: 16, fontWeight: '900', color: colors.slate900 },
  statLabel: { marginTop: 4, fontSize: 10, fontWeight: '700', color: colors.slate500 },
  infoCard: {
    backgroundColor: colors.white,
    borderRadius: 16,
    padding: 16,
    marginBottom: 12,
    borderWidth: 1,
    borderColor: colors.slate100,
  },
  infoLabel: { fontSize: 11, fontWeight: '800', color: colors.slate500, textTransform: 'uppercase' },
  infoValue: { marginTop: 4, fontSize: 15, fontWeight: '700', color: colors.slate900 },
  menuBtn: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 12,
    backgroundColor: colors.white,
    borderRadius: 16,
    padding: 16,
    marginBottom: 12,
    borderWidth: 1,
    borderColor: colors.slate100,
  },
  menuText: { flex: 1, fontSize: 15, fontWeight: '800', color: colors.baytgo },
  logoutBtn: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'center',
    gap: 8,
    backgroundColor: '#FEF2F2',
    borderRadius: 16,
    padding: 16,
    marginTop: 8,
  },
  logoutText: { fontSize: 15, fontWeight: '800', color: '#B91C1C' },
});
