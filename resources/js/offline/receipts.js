/**
 * Receipt Capture Module
 * Camera upload, image compression, storage, gallery
 */

import db from './database.js';
import { SyncStatus } from './store.js';

const MAX_IMAGE_WIDTH = 1200;
const MAX_IMAGE_HEIGHT = 1600;
const JPEG_QUALITY = 0.8;

/**
 * Capture a receipt from camera or file input
 */
export async function captureReceipt(file, transactionId = null, localId = null) {
    if (!file || !file.type.startsWith('image/')) {
        throw new Error('Invalid file: must be an image');
    }

    // Compress the image
    const compressed = await compressImage(file);

    // Convert to base64 for IndexedDB storage
    const base64 = await blobToBase64(compressed);

    const receipt = {
        transactionId,
        localId,
        fileName: file.name,
        mimeType: compressed.type,
        size: compressed.size,
        data: base64,
        syncStatus: SyncStatus.PENDING,
        createdAt: new Date().toISOString(),
    };

    const id = await db.receipts.add(receipt);
    return { id, ...receipt, data: undefined }; // Don't return base64 data in response
}

/**
 * Get receipts for a transaction
 */
export async function getReceiptsForTransaction(transactionId) {
    return await db.receipts
        .where('transactionId')
        .equals(transactionId)
        .toArray()
        .then((receipts) =>
            receipts.map(({ data, ...rest }) => ({
                ...rest,
                hasImage: !!data,
            }))
        );
}

/**
 * Get receipt image data by ID
 */
export async function getReceiptImage(receiptId) {
    const receipt = await db.receipts.get(receiptId);
    if (!receipt) return null;
    return {
        data: receipt.data,
        mimeType: receipt.mimeType,
        fileName: receipt.fileName,
    };
}

/**
 * Delete a receipt
 */
export async function deleteReceipt(receiptId) {
    await db.receipts.delete(receiptId);
}

/**
 * Get all receipts pending sync
 */
export async function getPendingReceipts() {
    return await db.receipts
        .where('syncStatus')
        .equals(SyncStatus.PENDING)
        .toArray()
        .then((receipts) =>
            receipts.map(({ data, ...rest }) => ({
                ...rest,
                hasImage: !!data,
            }))
        );
}

/**
 * Upload a receipt to the server
 */
export async function syncReceipt(receiptId, authToken) {
    const receipt = await db.receipts.get(receiptId);
    if (!receipt) throw new Error('Receipt not found');

    // Convert base64 back to blob for upload
    const blob = base64ToBlob(receipt.data, receipt.mimeType);
    const formData = new FormData();
    formData.append('receipt', blob, receipt.fileName);

    if (receipt.transactionId) {
        formData.append('transaction_id', receipt.transactionId);
    }

    const response = await fetch('/api/receipts', {
        method: 'POST',
        headers: {
            'Accept': 'application/json',
            'Authorization': `Bearer ${authToken}`,
        },
        body: formData,
    });

    if (!response.ok) {
        throw new Error(`Upload failed: ${response.statusText}`);
    }

    const result = await response.json();

    await db.receipts.update(receiptId, {
        syncStatus: SyncStatus.SYNCED,
        serverId: result.data?.id,
        syncedAt: new Date().toISOString(),
    });

    return result;
}

/**
 * Compress an image to reduce storage and bandwidth
 */
function compressImage(file) {
    return new Promise((resolve, reject) => {
        const img = new Image();
        const canvas = document.createElement('canvas');
        const ctx = canvas.getContext('2d');

        img.onload = () => {
            let { width, height } = img;

            // Scale down if too large
            if (width > MAX_IMAGE_WIDTH || height > MAX_IMAGE_HEIGHT) {
                const ratio = Math.min(MAX_IMAGE_WIDTH / width, MAX_IMAGE_HEIGHT / height);
                width = Math.round(width * ratio);
                height = Math.round(height * ratio);
            }

            canvas.width = width;
            canvas.height = height;
            ctx.drawImage(img, 0, 0, width, height);

            canvas.toBlob(
                (blob) => resolve(blob),
                'image/jpeg',
                JPEG_QUALITY
            );
        };

        img.onerror = () => reject(new Error('Failed to load image'));
        img.src = URL.createObjectURL(file);
    });
}

/**
 * Convert Blob to base64 string
 */
function blobToBase64(blob) {
    return new Promise((resolve, reject) => {
        const reader = new FileReader();
        reader.onload = () => resolve(reader.result);
        reader.onerror = reject;
        reader.readAsDataURL(blob);
    });
}

/**
 * Convert base64 string back to Blob
 */
function base64ToBlob(base64, mimeType) {
    const byteString = atob(base64.split(',')[1]);
    const ab = new ArrayBuffer(byteString.length);
    const ia = new Uint8Array(ab);
    for (let i = 0; i < byteString.length; i++) {
        ia[i] = byteString.charCodeAt(i);
    }
    return new Blob([ab], { type: mimeType });
}

/**
 * Get gallery view of all receipts
 */
export async function getReceiptGallery() {
    return await db.receipts
        .toArray()
        .then((receipts) =>
            receipts.map(({ data, ...rest }) => ({
                ...rest,
                hasImage: !!data,
                thumbnailUrl: data ? data : null,
            }))
        );
}
