import React, { useCallback, useMemo, useState } from 'react';
import {
  Alert,
  Platform,
  ScrollView,
  Share,
  StyleSheet,
  Text,
  View,
} from 'react-native';
import Constants, { ExecutionEnvironment } from 'expo-constants';
import * as Device from 'expo-device';
import { Bell, Copy, RefreshCw, Send } from 'lucide-react-native';
import ScreenHeader from '../components/ScreenHeader';
import { useAuth } from '../context/AuthContext';
import {
  getCachedExpoPushToken,
  getPushEnvironmentInfo,
  registerForPushNotificationsAsync,
  requestNotificationPermissionAsync,
  scheduleLocalTestNotification,
  syncPushTokenWithBackend,
} from '../notifications/pushNotifications';
import Button from '../ui/Button';
import Card from '../ui/Card';
import { colors, layout, radius, spacing, typography } from '../theme/tokens';
import { notifyError, notifySuccess } from '../utils/feedback';

function InfoRow({ label, value }) {
  return (
    <View style={styles.infoRow}>
      <Text style={styles.infoLabel}>{label}</Text>
      <Text style={styles.infoValue} selectable>{String(value ?? '—')}</Text>
    </View>
  );
}

export default function PushNotificationTestScreen({ navigation }) {
  const { token: authToken, isAuthenticated } = useAuth();
  const env = useMemo(() => getPushEnvironmentInfo(), []);
  const [busy, setBusy] = useState(false);
  const [permission, setPermission] = useState(null);
  const [expoToken, setExpoToken] = useState(getCachedExpoPushToken());
  const [lastLocalId, setLastLocalId] = useState(null);
  const [log, setLog] = useState([]);

  const appendLog = useCallback((line) => {
    setLog((prev) => [`${new Date().toLocaleTimeString('id-ID')}  ${line}`, ...prev].slice(0, 20));
  }, []);

  const isExpoGo = env.isExpoGo
    || Constants.executionEnvironment === ExecutionEnvironment.StoreClient;

  const refreshPermission = async () => {
    const status = await requestNotificationPermissionAsync();
    setPermission(status);
    appendLog(`Permission: ${status}`);
    return status;
  };

  const handleLocal = async (seconds = 2) => {
    setBusy(true);
    try {
      const id = await scheduleLocalTestNotification({
        title: 'BaytGo · Local Test',
        body: `Notifikasi lokal dalam ${seconds}s. Tap untuk uji navigasi chat.`,
        seconds,
      });
      setLastLocalId(id);
      appendLog(`Local scheduled: ${id}`);
      notifySuccess(`Notifikasi lokal dijadwalkan (${seconds}s)`);
    } catch (err) {
      appendLog(`Local failed: ${err.message}`);
      notifyError(err.message || 'Gagal jadwalkan lokal');
    } finally {
      setBusy(false);
    }
  };

  const handleGetToken = async () => {
    setBusy(true);
    try {
      await refreshPermission();
      if (isExpoGo) {
        throw new Error('Expo Go tidak mendukung remote push (SDK 53+). Pakai development build.');
      }
      const t = await registerForPushNotificationsAsync({ allowEmulator: true });
      setExpoToken(t);
      if (!t) {
        throw new Error('Token kosong. Pastikan development build + Google Play Services di emulator.');
      }
      appendLog(`Token: ${t}`);
      notifySuccess('Expo push token didapat');
    } catch (err) {
      appendLog(`Token failed: ${err.message}`);
      notifyError(err.message || 'Gagal ambil token');
    } finally {
      setBusy(false);
    }
  };

  const handleSyncBackend = async () => {
    if (!isAuthenticated || !authToken) {
      Alert.alert('Login dulu', 'Sync token ke backend butuh sesi login.');
      return;
    }
    setBusy(true);
    try {
      const t = await syncPushTokenWithBackend(authToken);
      setExpoToken(t);
      appendLog(t ? `Synced: ${t}` : 'Sync skipped (no token)');
      if (t) notifySuccess('Token tersimpan di backend');
      else notifyError('Tidak ada token untuk di-sync');
    } catch (err) {
      appendLog(`Sync failed: ${err.message}`);
      notifyError(err.message || 'Gagal sync backend');
    } finally {
      setBusy(false);
    }
  };

  const copyToken = async () => {
    if (!expoToken) {
      notifyError('Belum ada token');
      return;
    }
    try {
      await Share.share({ message: expoToken });
      appendLog('Token shared/copied');
    } catch {
      notifyError('Gagal bagikan token');
    }
  };

  return (
    <View style={styles.flex}>
      <ScreenHeader
        title="Test Push Notification"
        subtitle="DEV · emulator / device"
        onBack={() => navigation.goBack()}
      />
      <ScrollView contentContainerStyle={styles.content}>
        <Card style={styles.card}>
          <Text style={styles.heading}>Environment</Text>
          <InfoRow label="Platform" value={env.platform} />
          <InfoRow label="Model" value={env.modelName || Device.modelName} />
          <InfoRow label="isDevice" value={env.isDevice ? 'yes' : 'no (emulator)'} />
          <InfoRow label="Expo Go" value={isExpoGo ? 'YES — remote push OFF' : 'no'} />
          <InfoRow label="Exec env" value={env.executionEnvironment} />
          <InfoRow label="EAS projectId" value={env.projectId} />
          <InfoRow label="Permission" value={permission || 'belum dicek'} />
          <InfoRow label="Last local id" value={lastLocalId} />
        </Card>

        <Card style={styles.card}>
          <Text style={styles.heading}>1. Local notification (jalan di emulator)</Text>
          <Text style={styles.hint}>
            Uji banner + tap handler tanpa FCM. Bisa dipakai di Expo Go maupun development build.
          </Text>
          <Button
            label="Kirim lokal (2 detik)"
            onPress={() => handleLocal(2)}
            loading={busy}
            icon={<Bell size={16} color={colors.white} />}
          />
          <Button
            label="Kirim lokal (5 detik)"
            onPress={() => handleLocal(5)}
            loading={busy}
            variant="secondary"
          />
          <Button
            label="Cek / minta izin"
            onPress={refreshPermission}
            loading={busy}
            variant="ghost"
            icon={<RefreshCw size={16} color={colors.baytgo} />}
          />
        </Card>

        <Card style={styles.card}>
          <Text style={styles.heading}>2. Remote push token (butuh development build)</Text>
          <Text style={styles.hint}>
            Emulator butuh Google Play Services. Expo Go akan ditolak. Setelah dapat token, kirim via script:
            {'\n'}node scripts/send-test-push.js --token ExponentPushToken[...]
          </Text>
          <Text style={styles.tokenBox} selectable>
            {expoToken || 'Belum ada Expo push token'}
          </Text>
          <Button
            label="Ambil Expo push token"
            onPress={handleGetToken}
            loading={busy}
            icon={<Send size={16} color={colors.white} />}
          />
          <Button
            label="Bagikan / salin token"
            onPress={copyToken}
            variant="secondary"
            icon={<Copy size={16} color={colors.baytgo} />}
          />
          <Button
            label="Sync token ke backend"
            onPress={handleSyncBackend}
            loading={busy}
            variant="ghost"
          />
        </Card>

        <Card style={styles.card}>
          <Text style={styles.heading}>Log</Text>
          {log.length === 0 ? (
            <Text style={styles.hint}>Belum ada aktivitas.</Text>
          ) : (
            log.map((line) => (
              <Text key={line} style={styles.logLine} selectable>{line}</Text>
            ))
          )}
        </Card>
      </ScrollView>
    </View>
  );
}

const styles = StyleSheet.create({
  flex: { flex: 1, backgroundColor: colors.background },
  content: {
    paddingHorizontal: layout.screenPadding,
    paddingBottom: spacing.xxl,
    gap: spacing.md,
  },
  card: { gap: spacing.sm },
  heading: {
    ...typography.subtitle,
    fontSize: 15,
    color: colors.baytgo,
    fontFamily: 'PlusJakartaSans_800ExtraBold',
  },
  hint: {
    ...typography.caption,
    color: colors.textMuted,
    lineHeight: 18,
    marginBottom: spacing.xs,
  },
  infoRow: {
    flexDirection: 'row',
    gap: spacing.sm,
    paddingVertical: 4,
    borderBottomWidth: StyleSheet.hairlineWidth,
    borderBottomColor: colors.border,
  },
  infoLabel: {
    width: 110,
    ...typography.caption,
    color: colors.textSecondary,
    fontWeight: '600',
  },
  infoValue: {
    flex: 1,
    ...typography.caption,
    color: colors.textPrimary,
  },
  tokenBox: {
    ...typography.caption,
    color: colors.textPrimary,
    backgroundColor: colors.canvas,
    borderRadius: radius.sm,
    padding: spacing.md,
    borderWidth: 1,
    borderColor: colors.border,
  },
  logLine: {
    ...typography.small,
    color: colors.textSecondary,
    fontFamily: Platform.select({ ios: 'Menlo', android: 'monospace', default: 'monospace' }),
    marginBottom: 4,
  },
});
