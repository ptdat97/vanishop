/**
 * Ảnh poster cho video tải lên Thư viện (reference-parity L24): trình duyệt mở file video cục bộ, tua tới giây thứ nhất (hoặc
 * giữa video nếu ngắn hơn) và chụp khung hình bằng canvas → JPEG. Không cần ffmpeg trên server. Trình duyệt không giải mã được
 * (định dạng lạ, lỗi) → null: video vẫn dùng được, chỉ chưa có poster.
 */
export function capturePoster(file: File, maxWidth = 1280): Promise<Blob | null> {
    return new Promise((resolve) => {
        const source = URL.createObjectURL(file);
        const video = document.createElement('video');
        const done = (blob: Blob | null): void => {
            URL.revokeObjectURL(source);
            video.removeAttribute('src');
            video.load();
            resolve(blob);
        };
        const timer = window.setTimeout(() => done(null), 10_000);
        video.muted = true;
        video.playsInline = true;
        video.preload = 'auto';
        video.onerror = () => {
            window.clearTimeout(timer);
            done(null);
        };
        video.onloadedmetadata = () => {
            video.currentTime = Math.min(1, (video.duration || 0) / 2);
        };
        video.onseeked = () => {
            window.clearTimeout(timer);
            const scale = Math.min(1, maxWidth / (video.videoWidth || maxWidth));
            const canvas = document.createElement('canvas');
            canvas.width = Math.max(1, Math.round(video.videoWidth * scale));
            canvas.height = Math.max(1, Math.round(video.videoHeight * scale));
            const context = canvas.getContext('2d');
            if (!context || !video.videoWidth) {
                done(null);

                return;
            }
            context.drawImage(video, 0, 0, canvas.width, canvas.height);
            canvas.toBlob((blob) => done(blob), 'image/jpeg', 0.85);
        };
        video.src = source;
    });
}
