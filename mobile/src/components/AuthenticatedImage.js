import React, { useEffect, useState } from 'react';
import { View, Image, ActivityIndicator, StyleSheet } from 'react-native';
import * as FileSystem from 'expo-file-system/legacy';
import { colors } from '../theme/colors';

export default function AuthenticatedImage({ uri, token, style, imageStyle }) {
  const [localUri, setLocalUri] = useState(null);
  const [failed, setFailed] = useState(false);

  useEffect(() => {
    let cancelled = false;

    if (!uri) {
      setLocalUri(null);
      return undefined;
    }

    if (uri.startsWith('file://') || uri.startsWith('content://')) {
      setLocalUri(uri);
      setFailed(false);
      return undefined;
    }

    if (!token) {
      setLocalUri(null);
      return undefined;
    }

    (async () => {
      try {
        const cacheKey = uri.replace(/[^a-zA-Z0-9]/g, '_').slice(-80);
        const path = `${FileSystem.cacheDirectory}auth-${cacheKey}`;
        const info = await FileSystem.getInfoAsync(path);
        if (info.exists) {
          if (!cancelled) setLocalUri(path);
          return;
        }

        const result = await FileSystem.downloadAsync(uri, path, {
          headers: { Authorization: `Bearer ${token}`, Accept: '*/*' },
        });

        if (!cancelled) {
          if (result.status === 200) {
            setLocalUri(result.uri);
            setFailed(false);
          } else {
            setFailed(true);
          }
        }
      } catch {
        if (!cancelled) setFailed(true);
      }
    })();

    return () => {
      cancelled = true;
    };
  }, [uri, token]);

  if (failed) {
    return <View style={[styles.placeholder, style]} />;
  }

  if (!localUri) {
    return (
      <View style={[styles.placeholder, style]}>
        <ActivityIndicator color={colors.baytgo} size="small" />
      </View>
    );
  }

  return (
    <View style={style}>
      <Image source={{ uri: localUri }} style={[StyleSheet.absoluteFill, imageStyle]} resizeMode="cover" />
    </View>
  );
}

const styles = StyleSheet.create({
  placeholder: {
    backgroundColor: colors.slate100,
    alignItems: 'center',
    justifyContent: 'center',
  },
});
