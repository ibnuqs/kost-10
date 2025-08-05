// File: src/hooks/useRfidScanner.ts
import { useState, useEffect, useCallback, useRef } from 'react';
import { mqttService } from '../services/mqttService';

interface ScannedCard {
  uid: string;
  device_id: string;
  signal_strength?: number;
  timestamp: number;
}

interface ScannerState {
  isScanning: boolean;
  scannedCard: ScannedCard | null;
  error: string | null;
  timeoutId: number | null;
}

export const useRfidScanner = (scanTimeoutMs: number = 30000) => {
  const [state, setState] = useState<ScannerState>({
    isScanning: false,
    scannedCard: null,
    error: null,
    timeoutId: null
  });

  const scanTimeoutRef = useRef<NodeJS.Timeout | null>(null);
  const onCardScannedRef = useRef<((card: ScannedCard) => void) | null>(null);
  const isScanningRef = useRef<boolean>(false);

  // Handle MQTT scan messages
  const handleScanMessage = useCallback((topic: string, message: string) => {
    console.log('📡 RFID Scanner: Received message on', topic, ':', message);
    console.log('📡 Current scanning state:', isScanningRef.current);

    if (!isScanningRef.current) {
      console.log('⚠️ RFID Scanner: Not in scanning mode, ignoring message');
      return;
    }

    try {
      const parsedMessage = JSON.parse(message);
      
      // Check if this is an RFID scan from ESP32
      if (topic === 'rfid/tags' && parsedMessage.uid) {
        console.log('✨ RFID Scanner: Card detected!', parsedMessage);
        
        const scannedCard: ScannedCard = {
          uid: parsedMessage.uid.toUpperCase(),
          device_id: parsedMessage.device_id || 'ESP32-RFID-01',
          signal_strength: parsedMessage.signal_strength,
          timestamp: parsedMessage.timestamp || Date.now()
        };

        console.log('🎯 RFID Scanner: Processing scanned card:', scannedCard);
        
        // Update scanning ref first
        isScanningRef.current = false;
        
        // Update state with scanned card
        setState(prev => ({
          ...prev,
          scannedCard,
          error: null,
          isScanning: false // Stop scanning
        }));

        // Call callback if provided
        if (onCardScannedRef.current) {
          console.log('📞 RFID Scanner: Calling onCardScanned callback');
          onCardScannedRef.current(scannedCard);
        }

        console.log('✅ RFID Scanner: Scan completed successfully');
      }
    } catch (error) {
      console.error('❌ RFID Scanner: Error parsing scan message:', error);
      setState(prev => ({
        ...prev,
        error: 'Failed to parse scan data'
      }));
    }
  }, []); // Remove dependency to prevent re-creation

  // Stop scanning
  const stopScanning = useCallback(() => {
    console.log('🛑 RFID Scanner: Stopping scan mode...');
    
    // Check if already stopped
    if (!isScanningRef.current) {
      console.log('⚠️ RFID Scanner: Already stopped, skipping...');
      return;
    }
    
    // Update ref first
    isScanningRef.current = false;
    
    // Clear timeout
    if (scanTimeoutRef.current) {
      clearTimeout(scanTimeoutRef.current);
      scanTimeoutRef.current = null;
    }

    // Unsubscribe from topic
    mqttService.unsubscribe('rfid/tags', handleScanMessage);

    // Clear callback
    onCardScannedRef.current = null;

    // Update state
    setState(prev => ({
      ...prev,
      isScanning: false,
      timeoutId: null
    }));

    console.log('✅ RFID Scanner: Scan mode stopped');
  }, [handleScanMessage]); // Remove dependency to prevent re-creation

  // Start scanning for RFID cards
  const startScanning = useCallback((onCardScanned?: (card: ScannedCard) => void) => {
    console.log('🔍 RFID Scanner: Starting scan mode...');
    console.log('🔍 Current state:', { isScanning: state.isScanning, hasError: !!state.error });
    
    // Store callback
    onCardScannedRef.current = onCardScanned || null;

    // Check MQTT connection
    const connectionStatus = mqttService.getConnectionStatus();
    console.log('🔌 MQTT status:', connectionStatus);
    
    if (!connectionStatus.connected) {
      const error = 'MQTT not connected. Cannot start scanning.';
      console.error('❌ RFID Scanner:', error);
      setState(prev => ({
        ...prev,
        error,
        isScanning: false
      }));
      return false;
    }

    // Clear any existing timeout
    if (scanTimeoutRef.current) {
      clearTimeout(scanTimeoutRef.current);
    }

    // Subscribe to RFID scan topic
    mqttService.subscribe('rfid/tags', handleScanMessage);

    // Update scanning ref first
    isScanningRef.current = true;
    
    // Set scanning state
    setState(prev => ({
      ...prev,
      isScanning: true,
      scannedCard: null,
      error: null
    }));

    // Set timeout for scanning
    scanTimeoutRef.current = setTimeout(() => {
      console.log('⏰ RFID Scanner: Scan timeout reached');
      isScanningRef.current = false;
      stopScanning();
      setState(prev => ({
        ...prev,
        error: 'Scan timeout. Please try again.'
      }));
    }, scanTimeoutMs);

    console.log(`✅ RFID Scanner: Scan mode active (timeout: ${scanTimeoutMs}ms)`);
    return true;
  }, [scanTimeoutMs, handleScanMessage, state.isScanning, state.error, stopScanning]); // Remove handleScanMessage dependency

  // Clear scanned card data
  const clearScannedCard = useCallback(() => {
    setState(prev => ({
      ...prev,
      scannedCard: null,
      error: null
    }));
  }, []);

  // Clear error
  const clearError = useCallback(() => {
    setState(prev => ({
      ...prev,
      error: null
    }));
  }, []);

  // Cleanup on unmount
  useEffect(() => {
    return () => {
      stopScanning();
    };
  }, [stopScanning]);

  return {
    // State
    isScanning: state.isScanning,
    scannedCard: state.scannedCard,
    error: state.error,
    
    // Actions
    startScanning,
    stopScanning,
    clearScannedCard,
    clearError,
    
    // Status
    isConnected: mqttService.getConnectionStatus().connected,
    connectionStatus: mqttService.getConnectionStatus()
  };
};