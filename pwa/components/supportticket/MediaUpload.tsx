import React, {useCallback, useRef, useState} from 'react';
import {
  Alert,
  Box,
  Dialog,
  DialogContent,
  DialogTitle,
  IconButton,
  LinearProgress,
  Paper,
  Typography
} from '@mui/material';
import {Close, CloudUpload, Delete, Download, Image, VideoFile} from '@mui/icons-material';
import {useCreate, useDelete, useGetList} from 'react-admin';
import {useSession} from 'next-auth/react';
import {authenticatedFetch} from '../../utils/authenticatedFetch';

interface MediaFile {
  id: number;
  filename: string;
  originalName: string;
  mimeType: string;
  size: number;
  createdAt: string;
  downloadUrl: string;
  thumbnailUrl?: string;
}

interface MediaUploadProps {
  ticketId: string;
  onMediaChange?: () => void;
}

const ThumbnailImage: React.FC<{ src: string; alt: string; onClick: () => void }> = ({src, alt, onClick}) => {
  const [imageSrc, setImageSrc] = useState<string | null>(null);
  const [loading, setLoading] = useState(true);

  React.useEffect(() => {
    const loadImage = async () => {
      try {
        const response = await authenticatedFetch(src, {
          credentials: 'include'
        });
        if (response.ok) {
          const blob = await response.blob();
          const objectUrl = URL.createObjectURL(blob);
          setImageSrc(objectUrl);
        }
      } catch (error) {
        console.error('Failed to load thumbnail:', error);
      } finally {
        setLoading(false);
      }
    };

    if (src) {
      loadImage();
    }
  }, [src]);

  if (loading) {
    return <Box sx={{
      width: 60,
      height: 60,
      bgcolor: 'grey.200',
      display: 'flex',
      alignItems: 'center',
      justifyContent: 'center'
    }}>...</Box>;
  }

  if (!imageSrc) {
    return <Image color="primary"/>;
  }

  return (
    <img
      src={imageSrc}
      alt={alt}
      style={{
        width: '100%',
        height: '100%',
        objectFit: 'cover',
        borderRadius: 4,
        cursor: 'pointer'
      }}
      onClick={onClick}
    />
  );
};

export const MediaUpload: React.FC<MediaUploadProps> = ({ticketId, onMediaChange}) => {
  const [dragOver, setDragOver] = useState(false);
  const [uploading, setUploading] = useState(false);
  const [uploadProgress, setUploadProgress] = useState(0);
  const [error, setError] = useState<string | null>(null);

  const [dialogOpen, setDialogOpen] = useState(false);

  const [selectedMedia, setSelectedMedia] = useState<MediaFile | null>(null);

  const [create] = useCreate();
  const [deleteOne] = useDelete();

  const {data: mediaFiles, isLoading, refetch} = useGetList(
    'support_ticket_media',
    {
      filter: {supportTicket: `/api/support_tickets/${ticketId}`},
      sort: {field: 'createdAt', order: 'ASC'},
    }
  );

  const handleDragOver = useCallback((e: React.DragEvent) => {
    e.preventDefault();
    setDragOver(true);
  }, []);

  const handleDragLeave = useCallback((e: React.DragEvent) => {
    e.preventDefault();
    setDragOver(false);
  }, []);

  const handleDrop = useCallback(async (e: React.DragEvent) => {
    e.preventDefault();
    setDragOver(false);

    const files = Array.from(e.dataTransfer.files);
    if (files.length === 0) return;

    await uploadFiles(files);
  }, [ticketId]);

  const handleFileSelect = useCallback(async (e: React.ChangeEvent<HTMLInputElement>) => {
    const files = Array.from(e.target.files || []);
    if (files.length === 0) return;

    await uploadFiles(files);
  }, [ticketId]);

  const uploadFiles = async (files: File[]) => {
    setUploading(true);
    setError(null);
    setUploadProgress(0);

    try {
      for (let i = 0; i < files.length; i++) {
        const file = files[i];
        const formData = new FormData();
        formData.append('file', file);

        await create(
          `support_tickets/${ticketId}/media`,
          {
            data: formData,
          }
        );

        setUploadProgress(((i + 1) / files.length) * 100);
      }

      refetch();
      onMediaChange?.();
    } catch (err) {
      setError('Ошибка при загрузке файлов');
      console.error('Upload error:', err);
    } finally {
      setUploading(false);
      setUploadProgress(0);
    }
  };

  const handleDelete = async (mediaId: number) => {
    try {
      await deleteOne('support_ticket_media', {id: mediaId});
      refetch();
      onMediaChange?.();
    } catch (err) {
      setError('Ошибка при удалении файла');
      console.error('Delete error:', err);
    }
  };

  const handleDownload = async (media: MediaFile) => {
    try {
      const response = await authenticatedFetch(media.downloadUrl);
      if (response.ok) {
        const blob = await response.blob();
        const url = URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = media.originalName;
        document.body.appendChild(a);
        a.click();
        document.body.removeChild(a);
        URL.revokeObjectURL(url);
      } else {
        setError('Ошибка при скачивании файла');
      }
    } catch (error) {
      setError('Ошибка при скачивании файла');
      console.error('Download error:', error);
    }
  };

  const handlePreview = useCallback((media: MediaFile) => {
    setSelectedMedia(media);
    setDialogOpen(true);
  }, []);

  const handleCloseDialog = useCallback(() => {
    setDialogOpen(false);
    setSelectedMedia(null);
  }, []);

  const formatFileSize = (bytes: number) => {
    if (bytes === 0) return '0 Bytes';
    const k = 1024;
    const sizes = ['Bytes', 'KB', 'MB', 'GB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
  };

  const isImage = (mimeType: string) => mimeType.startsWith('image/');
  const isVideo = (mimeType: string) => mimeType.startsWith('video/');

  const FullMediaViewer = React.memo(({media, onClose, open}: { media: MediaFile | null; onClose: () => void; open: boolean }) => {
    if (!media) return null;
    const {data: session, status} = useSession();
    const [mediaSrc, setMediaSrc] = useState<string | null>(null);
    const [loading, setLoading] = useState(true);
    const [loaded, setLoaded] = useState(false);
    const [hasError, setHasError] = useState(false);
    const loadingRef = useRef(false);
    const attemptedLoadRef = useRef(false);

      React.useEffect(() => {
        if (!media) return;
        setLoaded(false);
        setLoading(true);
        setMediaSrc(null);
        setHasError(false);
        loadingRef.current = false;
        attemptedLoadRef.current = false;
      }, [media?.id]);

      React.useEffect(() => {
        return () => {
          if (mediaSrc) {
            URL.revokeObjectURL(mediaSrc);
          }
        };
      }, [mediaSrc]);

      React.useEffect(() => {
        if (loaded || loadingRef.current || attemptedLoadRef.current) return;

        loadingRef.current = true;
        setLoading(true);

        const loadMedia = async () => {
          attemptedLoadRef.current = true;
          if (status !== 'authenticated' || !session) {
            setHasError(true);
            setLoading(false);
            loadingRef.current = false;
            return;
          }
          try {
            const response = await fetch(media.downloadUrl, {
              headers: {
                'Authorization': `Bearer ${session.accessToken}`,
              },
            });
            if (response.ok) {
              const blob = await response.blob();
              const objectUrl = URL.createObjectURL(blob);
              setMediaSrc(objectUrl);
              setLoaded(true);
            } else {
              setHasError(true);
            }
          } catch (error) {
            console.error('Failed to load media:', error);
            setHasError(true);
          } finally {
            setLoading(false);
            loadingRef.current = false;
          }
        };

        loadMedia();
      }, [media?.downloadUrl, loaded]);

      return (
        <Dialog open={open} onClose={onClose} maxWidth="lg" sx={{height: '70vh'}}>
          <DialogTitle>
            {media.originalName}
            <IconButton
              aria-label="close"
              onClick={onClose}
              sx={{
                position: 'absolute',
                right: 8,
                top: 8,
                color: (theme) => theme.palette.grey[500],
              }}
            >
              <Close/>
            </IconButton>
          </DialogTitle>
          <DialogContent sx={{height: '100%', display: 'flex', alignItems: 'center', justifyContent: 'center'}}>
            {loading ? (
              <Typography>Загрузка...</Typography>
            ) : mediaSrc ? (
              isImage(media.mimeType) ? (
                <img
                  src={mediaSrc}
                  alt={media.originalName}
                  style={{
                    maxWidth: '100%',
                    maxHeight: '100%',
                    objectFit: 'contain',
                    display: 'block'
                  }}
                />
              ) : isVideo(media.mimeType) ? (
                <video
                  controls
                  src={mediaSrc}
                  style={{
                    maxWidth: '100%',
                    maxHeight: '100%',
                    display: 'block'
                  }}
                />
              ) : null
            ) : (
              <Typography>Не удалось загрузить медиа</Typography>
            )}
          </DialogContent>
        </Dialog>
      );
    }
  );

  return (
    <Box>
      <Typography variant="h6" gutterBottom>
        Медиа файлы
      </Typography>

      {/* Upload Area */}
      <Paper
        sx={{
          p: 3,
          mb: 2,
          border: '2px dashed',
          borderColor: dragOver ? 'primary.main' : 'grey.300',
          backgroundColor: dragOver ? 'primary.50' : 'grey.50',
          cursor: 'pointer',
          transition: 'all 0.2s ease',
          position: 'relative',
        }}
        onDragOver={handleDragOver}
        onDragLeave={handleDragLeave}
        onDrop={handleDrop}
        onClick={() => document.getElementById('file-input')?.click()}
      >
        <input
          id="file-input"
          type="file"
          multiple
          accept="image/*,video/*"
          style={{display: 'none'}}
          onChange={handleFileSelect}
        />

        <Box textAlign="center">
          <CloudUpload sx={{fontSize: 48, color: 'grey.400', mb: 1}}/>
          <Typography variant="body1" color="textSecondary">
            Перетащите файлы сюда или нажмите для выбора
          </Typography>
          <Typography variant="body2" color="textSecondary">
            Поддерживаются изображения и видео
          </Typography>
        </Box>

        {uploading && (
          <Box sx={{mt: 2}}>
            <LinearProgress variant="determinate" value={uploadProgress}/>
            <Typography variant="body2" color="textSecondary" sx={{mt: 1}}>
              Загрузка... {Math.round(uploadProgress)}%
            </Typography>
          </Box>
        )}
      </Paper>

      {error && (
        <Alert severity="error" sx={{mb: 2}}>
          {error}
        </Alert>
      )}

      {/* Media Files List */}
      {mediaFiles && mediaFiles.length > 0 && (
        <Box>
          <Typography variant="subtitle1" gutterBottom>
            Загруженные файлы ({mediaFiles.length})
          </Typography>

          {mediaFiles.map((media: MediaFile) => (
            <Paper key={media.id} sx={{p: 2, mb: 1, display: 'flex', alignItems: 'center'}}>
              <Box sx={{mr: 2, width: 200, height: 200, display: 'flex', alignItems: 'center', justifyContent: 'center', boxShadow: 2}}>
                {media.thumbnailUrl ? (
                  <ThumbnailImage
                    src={media.thumbnailUrl}
                    alt={media.originalName}
                    onClick={() => handlePreview(media)}
                  />
                ) : (
                  <>
                    {isImage(media.mimeType) && <Image color="primary"/>}
                    {isVideo(media.mimeType) && <VideoFile color="primary"/>}
                  </>
                )}
              </Box>

              <Box sx={{flexGrow: 1}}>
                <Typography variant="body1" noWrap>
                  {media.originalName}
                </Typography>
                <Typography variant="body2" color="textSecondary">
                  {formatFileSize(media.size)} • {new Date(media.createdAt).toLocaleDateString('ru-RU')}
                </Typography>
              </Box>

              <Box>
                <IconButton onClick={() => handleDownload(media)} size="small">
                  <Download/>
                </IconButton>
                <IconButton onClick={() => handleDelete(media.id)} size="small" color="error">
                  <Delete/>
                </IconButton>
              </Box>
            </Paper>
          ))}
        </Box>
      )}

      {isLoading && (
        <Typography variant="body2" color="textSecondary">
          Загрузка файлов...
        </Typography>
      )}

      <FullMediaViewer media={selectedMedia} onClose={handleCloseDialog} open={dialogOpen}/>
    </Box>
  );
};
