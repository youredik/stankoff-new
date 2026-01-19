import React, {useCallback, useRef, useState} from 'react';
import {
  Alert,
  Box,
  Button,
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
import {getSession, useSession} from 'next-auth/react';
import {type Session} from '../../app/auth';
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

interface UploadingFile {
  file: File;
  progress: number;
  status: 'uploading' | 'done' | 'error';
  previewUrl?: string;
}

interface MediaUploadProps {
  ticketId: string;
  onMediaChange?: () => void;
}

const ThumbnailImage: React.FC<{ src: string; alt: string; onClick: (e: React.MouseEvent) => void }> = ({
                                                                                                          src,
                                                                                                          alt,
                                                                                                          onClick
                                                                                                        }) => {
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
      onClick={(e) => onClick(e)}
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

  const [uploadingFiles, setUploadingFiles] = useState<UploadingFile[]>([]);

  const [deleteConfirmOpen, setDeleteConfirmOpen] = useState(false);
  const [fileToDelete, setFileToDelete] = useState<MediaFile | null>(null);
  const [deletingIds, setDeletingIds] = useState<Set<number>>(new Set());

  const [create] = useCreate();
  const [deleteOne] = useDelete();

  const {data: mediaFiles, isLoading, refetch} = useGetList(
    `support_tickets/${ticketId}/media`,
    {
      sort: {field: 'createdAt', order: 'ASC'},
    }
  );

  // Clean up object URLs on unmount
  React.useEffect(() => {
    return () => {
      uploadingFiles.forEach(file => {
        if (file.previewUrl) {
          URL.revokeObjectURL(file.previewUrl);
        }
      });
    };
  }, []);

  // Clear completed uploads after a delay
  React.useEffect(() => {
    if (uploadingFiles.length > 0 && uploadingFiles.every(f => f.status !== 'uploading')) {
      const timer = setTimeout(() => {
        refetch();
        setUploadingFiles(prev => {
          prev.forEach(file => {
            if (file.previewUrl) {
              URL.revokeObjectURL(file.previewUrl);
            }
          });
          return [];
        });
      }, 2000); // 2 seconds delay
      return () => clearTimeout(timer);
    }
  }, [uploadingFiles, refetch]);

  const handleDragOver = useCallback((e: React.DragEvent) => {
    e.preventDefault();
    setDragOver(true);
  }, []);

  const handleDragLeave = useCallback((e: React.DragEvent) => {
    e.preventDefault();
    setDragOver(false);
  }, []);

  const handleDrop = useCallback((e: React.DragEvent) => {
    e.preventDefault();
    setDragOver(false);

    const files = Array.from(e.dataTransfer.files);
    if (files.length === 0) return;

    startUploads(files);
  }, [ticketId]);

  const handleFileSelect = useCallback((e: React.ChangeEvent<HTMLInputElement>) => {
    const files = Array.from(e.target.files || []);
    if (files.length === 0) return;

    startUploads(files);
  }, [ticketId]);

  const startUploads = useCallback((files: File[]) => {
    const newUploadingFiles: UploadingFile[] = files.map(file => ({
      file,
      progress: 0,
      status: 'uploading' as const,
      previewUrl: file.type.startsWith('image/') ? URL.createObjectURL(file) : undefined,
    }));
    setUploadingFiles(prev => [...prev, ...newUploadingFiles]);

    // Start uploading each file
    newUploadingFiles.forEach((uploadingFile, index) => {
      uploadSingleFile(uploadingFile, index + uploadingFiles.length);
    });
  }, [uploadingFiles.length]);

  const uploadSingleFile = useCallback(async (uploadingFile: UploadingFile, index: number) => {
    const session = await getSession() as Session | null;
    if (!session?.accessToken) {
      setUploadingFiles(prev => prev.map((f, i) => i === index ? {...f, status: 'error'} : f));
      return;
    }

    const xhr = new XMLHttpRequest();
    const formData = new FormData();
    formData.append('file', uploadingFile.file);

    xhr.upload.addEventListener('progress', (event) => {
      if (event.lengthComputable) {
        const progress = (event.loaded / event.total) * 100;
        setUploadingFiles(prev => prev.map((f, i) => i === index ? {...f, progress} : f));
      }
    });

    xhr.addEventListener('load', () => {
      if (xhr.status === 201) {
        setUploadingFiles(prev => prev.map((f, i) => i === index ? {...f, status: 'done', progress: 100} : f));
        onMediaChange?.();
      } else {
        setUploadingFiles(prev => prev.map((f, i) => i === index ? {...f, status: 'error'} : f));
      }
    });

    xhr.addEventListener('error', () => {
      setUploadingFiles(prev => prev.map((f, i) => i === index ? {...f, status: 'error'} : f));
    });

    xhr.open('POST', `/support_tickets/${ticketId}/media`);
    xhr.setRequestHeader('Authorization', `Bearer ${session.accessToken}`);
    xhr.send(formData);
  }, [ticketId, refetch, onMediaChange]);


  const handleDeleteClick = (media: MediaFile) => {
    setFileToDelete(media);
    setDeleteConfirmOpen(true);
  };

  const handleDeleteConfirm = async () => {
    if (!fileToDelete) return;
    const fileId = fileToDelete.id;
    setDeleteConfirmOpen(false);
    setFileToDelete(null);
    setDeletingIds(prev => new Set(prev).add(fileId));
    try {
      await deleteOne(`support_tickets/${ticketId}/media`, {id: fileId});
      refetch();
      onMediaChange?.();
    } catch (err) {
      setError('Ошибка при удалении файла');
      console.error('Delete error:', err);
    } finally {
      setDeletingIds(prev => {
        const newSet = new Set(prev);
        newSet.delete(fileId);
        return newSet;
      });
    }
  };

  const handleDeleteCancel = () => {
    setDeleteConfirmOpen(false);
    setFileToDelete(null);
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

  const FullMediaViewer = React.memo(({media, onClose, open}: {
      media: MediaFile | null;
      onClose: () => void;
      open: boolean
    }) => {
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
                'Authorization': `Bearer ${(session as Session).accessToken}`,
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
        Фото и видео ({(mediaFiles?.length || 0) + uploadingFiles.length})
      </Typography>


      {error && (
        <Alert severity="error" sx={{mb: 2}}>
          {error}
        </Alert>
      )}

      {/* Media Files List */}
      <Box
        sx={{
          minHeight: 200,
          border: dragOver ? '2px dashed' : '2px dashed transparent',
          borderColor: dragOver ? 'primary.main' : 'transparent',
          backgroundColor: dragOver ? 'primary.50' : 'transparent',
          transition: 'all 0.2s ease',
          p: dragOver ? 2 : 0,
          borderRadius: 1,
        }}
        onDragOver={handleDragOver}
        onDragLeave={handleDragLeave}
        onDrop={handleDrop}
        onClick={() => !dragOver && document.getElementById('file-input')?.click()}
      >
        <input
          id="file-input"
          type="file"
          multiple
          accept="image/*,video/*"
          style={{display: 'none'}}
          onChange={handleFileSelect}
        />
        {(mediaFiles && mediaFiles.length > 0) || uploadingFiles.length > 0 ? (
          <>

            <Box sx={{display: 'flex', flexWrap: 'wrap', gap: 2, pb: 1}}>
              {mediaFiles && mediaFiles.map((media: MediaFile) => (
                <Paper key={media.id} sx={{
                  p: 2,
                  minWidth: 250,
                  display: 'flex',
                  flexDirection: 'column',
                  alignItems: 'center',
                  position: 'relative',
                  animation: deletingIds.has(media.id) ? 'pulse 1s infinite' : 'none',
                  '@keyframes pulse': {
                    '0%': { transform: 'scale(1)' },
                    '50%': { transform: 'scale(1.05)' },
                    '100%': { transform: 'scale(1)' },
                  },
                }}>
                  {deletingIds.has(media.id) && (
                    <Box sx={{
                      position: 'absolute',
                      top: 0,
                      left: 0,
                      right: 0,
                      bottom: 0,
                      bgcolor: 'rgba(255, 255, 255, 0.8)',
                      display: 'flex',
                      alignItems: 'center',
                      justifyContent: 'center',
                      zIndex: 1,
                      borderRadius: 1
                    }}>
                      <Typography variant="body2" color="textSecondary">
                        Удаление...
                      </Typography>
                    </Box>
                  )}
                  <Box sx={{
                    width: 200,
                    height: 200,
                    display: 'flex',
                    alignItems: 'center',
                    justifyContent: 'center',
                    boxShadow: 2,
                    mb: 1
                  }}>
                    {media.thumbnailUrl ? (
                      <ThumbnailImage
                        src={media.thumbnailUrl}
                        alt={media.originalName}
                        onClick={(e) => {
                          e.stopPropagation();
                          handlePreview(media);
                        }}
                      />
                    ) : (
                      <>
                        {isImage(media.mimeType) && <Image color="primary"/>}
                        {isVideo(media.mimeType) && <VideoFile color="primary"/>}
                      </>
                    )}
                  </Box>

                  <Box sx={{textAlign: 'center', mb: 1}}>
                    <Typography variant="body1" noWrap sx={{maxWidth: 200}}>
                      {media.originalName}
                    </Typography>
                    <Typography variant="body2" color="textSecondary">
                      {formatFileSize(media.size)} • {new Date(media.createdAt).toLocaleDateString('ru-RU')}
                    </Typography>
                  </Box>

                  <Box sx={{display: 'flex', gap: 1}}>
                    <IconButton onClick={(e) => {
                      e.stopPropagation();
                      handleDownload(media);
                    }} size="small">
                      <Download/>
                    </IconButton>
                    <IconButton onClick={(e) => {
                      e.stopPropagation();
                      handleDeleteClick(media);
                    }} size="small" color="error">
                      <Delete/>
                    </IconButton>
                  </Box>
                </Paper>
              ))}
              {uploadingFiles.map((uploadingFile, index) => (
                <Paper key={`uploading-${index}`}
                       sx={{p: 2, minWidth: 250, display: 'flex', flexDirection: 'column', alignItems: 'center'}}>
                  <Box sx={{
                    width: 200,
                    height: 200,
                    display: 'flex',
                    alignItems: 'center',
                    justifyContent: 'center',
                    boxShadow: 2,
                    mb: 1,
                    position: 'relative'
                  }}>
                    {uploadingFile.previewUrl ? (
                      <img
                        src={uploadingFile.previewUrl}
                        alt={uploadingFile.file.name}
                        style={{
                          width: '100%',
                          height: '100%',
                          objectFit: 'cover',
                          borderRadius: 4,
                          opacity: uploadingFile.status === 'done' ? 1 : 0.7,
                        }}
                        onClick={(e) => e.stopPropagation()}
                      />
                    ) : (
                      <>
                        {uploadingFile.file.type.startsWith('video/') ? <VideoFile color="primary"/> :
                          <Image color="primary"/>}
                      </>
                    )}
                    {uploadingFile.status === 'uploading' && (
                      <Box sx={{
                        position: 'absolute',
                        top: 0,
                        left: 0,
                        right: 0,
                        bottom: 0,
                        display: 'flex',
                        alignItems: 'center',
                        justifyContent: 'center',
                        bgcolor: 'rgba(0,0,0,0.5)',
                        borderRadius: 1
                      }}>
                        <Typography variant="body2" color="white">
                          {Math.round(uploadingFile.progress)}%
                        </Typography>
                      </Box>
                    )}
                    {uploadingFile.status === 'error' && (
                      <Box sx={{
                        position: 'absolute',
                        top: 0,
                        left: 0,
                        right: 0,
                        bottom: 0,
                        display: 'flex',
                        alignItems: 'center',
                        justifyContent: 'center',
                        bgcolor: 'rgba(255,0,0,0.5)',
                        borderRadius: 1
                      }}>
                        <Typography variant="body2" color="white">
                          Ошибка
                        </Typography>
                      </Box>
                    )}
                  </Box>
                  <Box sx={{textAlign: 'center', mb: 1}}>
                    <Typography variant="body1" noWrap sx={{maxWidth: 200}}>
                      {uploadingFile.file.name}
                    </Typography>
                    <Typography variant="body2" color="textSecondary">
                      {formatFileSize(uploadingFile.file.size)}
                    </Typography>
                  </Box>
                  {uploadingFile.status === 'uploading' && (
                    <LinearProgress variant="determinate" value={uploadingFile.progress} sx={{width: '100%'}}/>
                  )}
                </Paper>
              ))}
            </Box>
          </>
        ) : (
          <Box sx={{textAlign: 'center', py: 4}}>
            <CloudUpload sx={{fontSize: 48, color: 'grey.400', mb: 1}}/>
            <Typography variant="body1" color="textSecondary">
              Перетащите файлы сюда или нажмите для выбора
            </Typography>
            <Typography variant="body2" color="textSecondary">
              Поддерживаются изображения и видео
            </Typography>
          </Box>
        )}
      </Box>

      {isLoading && (
        <Typography variant="body2" color="textSecondary">
          Загрузка файлов...
        </Typography>
      )}

      <FullMediaViewer media={selectedMedia} onClose={handleCloseDialog} open={dialogOpen}/>

      {/* Delete Confirmation Dialog */}
      <Dialog open={deleteConfirmOpen} onClose={handleDeleteCancel}>
        <DialogTitle>Подтверждение удаления</DialogTitle>
        <DialogContent>
          <Typography>
            Вы уверены, что хотите удалить файл "{fileToDelete?.originalName}"? Это действие нельзя отменить.
          </Typography>
        </DialogContent>
        <Box sx={{display: 'flex', justifyContent: 'flex-end', p: 2, gap: 1}}>
          <Button onClick={handleDeleteCancel}>Отмена</Button>
          <Button onClick={handleDeleteConfirm} color="error" variant="contained">
            Удалить
          </Button>
        </Box>
      </Dialog>
    </Box>
  );
};
