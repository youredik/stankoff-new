import React, {useCallback, useState} from 'react';
import {
  Alert,
  Box,
  Button,
  CircularProgress,
  Dialog,
  DialogContent,
  DialogTitle,
  IconButton,
  LinearProgress,
  Paper,
  Typography
} from '@mui/material';
import {CloudUpload, Delete, Download, Image, VideoFile} from '@mui/icons-material';
import {useDelete, useGetList} from 'react-admin';
import {getSession} from 'next-auth/react';
import {type Session} from '../../app/auth';
import {authenticatedFetch} from '../../utils/authenticatedFetch';
import Lightbox, {type Slide} from "yet-another-react-lightbox";
import "yet-another-react-lightbox/styles.css";
import Fullscreen from "yet-another-react-lightbox/plugins/fullscreen";
import Video from "yet-another-react-lightbox/plugins/video";
import Zoom from "yet-another-react-lightbox/plugins/zoom";

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
      display: 'flex',
      alignItems: 'center',
      justifyContent: 'center'
    }}>
      <CircularProgress size={30}/>
    </Box>;
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
  const [error, setError] = useState<string | null>(null);
  const [lightboxOpen, setLightboxOpen] = useState(false);
  const [currentIndex, setCurrentIndex] = useState(0);
  const [slides, setSlides] = useState<Slide[]>([]);
  const [uploadingFiles, setUploadingFiles] = useState<UploadingFile[]>([]);
  const [deleteConfirmOpen, setDeleteConfirmOpen] = useState(false);
  const [fileToDelete, setFileToDelete] = useState<MediaFile | null>(null);
  const [deletingIds, setDeletingIds] = useState<Set<number>>(new Set());
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
    if (!mediaFiles) return;
    const index = mediaFiles.findIndex(m => m.id === media.id);
    if (index === -1) return;
    setCurrentIndex(index);
    setSlides(mediaFiles.map(m => ({type: isVideo(m.mimeType) ? 'video' : 'image'} as Slide)));

    // For videos, open lightbox immediately and load video sources
    if (isVideo(media.mimeType)) {
      setLightboxOpen(true);
      // Load video src
      authenticatedFetch(media.downloadUrl)
        .then(response => {
          if (response.ok) {
            return response.blob();
          }
          throw new Error('Failed to fetch video');
        })
        .then(async blob => {
          const url = URL.createObjectURL(blob);
          // Get video dimensions
          const video = document.createElement('video');
          video.src = url;
          await new Promise((resolve) => {
            video.onloadedmetadata = resolve;
          });
          const width = video.videoWidth;
          const height = video.videoHeight;
          video.remove();

          let posterUrl: string | undefined;
          if (media.thumbnailUrl) {
            try {
              const thumbResponse = await authenticatedFetch(media.thumbnailUrl);
              if (thumbResponse.ok) {
                const thumbBlob = await thumbResponse.blob();
                const thumbUrl = URL.createObjectURL(thumbBlob);
                const img = document.createElement('img');
                img.src = thumbUrl;
                await new Promise((resolve) => {
                  img.onload = resolve;
                });
                const canvas = document.createElement('canvas');
                canvas.width = width;
                canvas.height = height;
                const ctx = canvas.getContext('2d');
                if (ctx) {
                  // Scale thumbnail to fit video dimensions
                  const scale = Math.min(width / img.width, height / img.height);
                  const scaledWidth = img.width * scale;
                  const scaledHeight = img.height * scale;
                  const x = (width - scaledWidth) / 2;
                  const y = (height - scaledHeight) / 2;
                  ctx.drawImage(img, x, y, scaledWidth, scaledHeight);
                  posterUrl = canvas.toDataURL();
                }
                URL.revokeObjectURL(thumbUrl);
              }
            } catch (e) {
              console.error('Failed to load video poster:', e);
            }
          }

          setSlides(prev => {
            const newS = [...prev];
            newS[index] = {
              type: 'video',
              sources: [{src: url, type: media.mimeType}],
              poster: posterUrl,
              width,
              height
            } as Slide;
            return newS;
          });
        })
        .catch(e => {
          console.error('Failed to load video:', e);
        });
    } else {
      // For images, load thumbnails first for quick view
      mediaFiles.forEach(async (m, i) => {
        if (m.thumbnailUrl) {
          try {
            const response = await authenticatedFetch(m.thumbnailUrl);
            if (response.ok) {
              const blob = await response.blob();
              const thumbUrl = URL.createObjectURL(blob);
              setSlides(prev => {
                const newS = [...prev];
                newS[i] = {type: 'image', src: thumbUrl} as Slide;
                return newS;
              });
              if (i === index) {
                setLightboxOpen(true);
              }
            }
          } catch (e) {
            console.error('Failed to load thumbnail:', e);
          }
        }
      });
    }

    // Then load originals in background for all
    mediaFiles.forEach(async (m, i) => {
      if (i === index && isVideo(m.mimeType)) return; // Already loaded for current video
      try {
        const response = await authenticatedFetch(m.downloadUrl);
        if (response.ok) {
          const blob = await response.blob();
          const url = URL.createObjectURL(blob);
          let posterUrl: string | undefined;
          let width: number | undefined;
          let height: number | undefined;
          if (isVideo(m.mimeType)) {
            if (m.thumbnailUrl) {
              try {
                const thumbResponse = await authenticatedFetch(m.thumbnailUrl);
                if (thumbResponse.ok) {
                  const thumbBlob = await thumbResponse.blob();
                  const thumbUrl = URL.createObjectURL(thumbBlob);
                  const img = document.createElement('img');
                  img.src = thumbUrl;
                  await new Promise((resolve) => {
                    img.onload = resolve;
                  });
                  const canvas = document.createElement('canvas');
                  canvas.width = width!;
                  canvas.height = height!;
                  const ctx = canvas.getContext('2d');
                  if (ctx) {
                    // Scale thumbnail to fit video dimensions
                    const scale = Math.min(width! / img.width, height! / img.height);
                    const scaledWidth = img.width * scale;
                    const scaledHeight = img.height * scale;
                    const x = (width! - scaledWidth) / 2;
                    const y = (height! - scaledHeight) / 2;
                    ctx.drawImage(img, x, y, scaledWidth, scaledHeight);
                    posterUrl = canvas.toDataURL();
                  }
                  URL.revokeObjectURL(thumbUrl);
                }
              } catch (e) {
                console.error('Failed to load video poster:', e);
              }
            }
            // Get video dimensions
            const video = document.createElement('video');
            video.src = url;
            await new Promise((resolve) => {
              video.onloadedmetadata = resolve;
            });
            width = video.videoWidth;
            height = video.videoHeight;
            video.remove();
          }
          setSlides(prev => {
            const newS = [...prev];
            // Revoke thumbnail URL if different
            if ('src' in newS[i] && newS[i].src && newS[i].src !== url) {
              URL.revokeObjectURL(newS[i].src!);
            }
            if (isVideo(m.mimeType)) {
              newS[i] = {
                type: 'video',
                sources: [{src: url, type: m.mimeType}],
                poster: posterUrl,
                width,
                height
              } as Slide;
            } else {
              newS[i] = {type: 'image', src: url} as Slide;
            }
            return newS;
          });
        }
      } catch (e) {
        console.error('Failed to load media:', e);
      }
    });
  }, [mediaFiles]);


  const formatFileSize = (bytes: number) => {
    if (bytes === 0) return '0 Bytes';
    const k = 1024;
    const sizes = ['Bytes', 'KB', 'MB', 'GB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
  };

  const isImage = (mimeType: string) => mimeType.startsWith('image/');
  const isVideo = (mimeType: string) => mimeType.startsWith('video/');


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
        sx={(theme) => ({
          minHeight: 200,
          border: dragOver ? '2px dashed' : '1px solid',
          borderColor: dragOver ? theme.palette.primary.main : theme.palette.divider,
          backgroundColor: dragOver
            ? (theme.palette.mode === 'dark' ? 'rgba(25, 118, 210, 0.15)' : 'primary.50')
            : (theme.palette.mode === 'dark' ? theme.palette.grey[900] : 'grey.50'),
          transition: 'all 0.2s ease',
          p: 2,
          borderRadius: 1,
          cursor: 'pointer',
        })}
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
                    '0%': {transform: 'scale(1)'},
                    '50%': {transform: 'scale(1.05)'},
                    '100%': {transform: 'scale(1)'},
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

      <Lightbox
        open={lightboxOpen}
        close={() => {
          setLightboxOpen(false);
          // Revoke URLs
          slides.forEach(slide => {
            if (slide.type === 'image' && 'src' in slide && slide.src) {
              URL.revokeObjectURL(slide.src);
            }
            if (slide.type === 'video' && 'sources' in slide && slide.sources) {
              slide.sources.forEach((source: { src: string }) => {
                URL.revokeObjectURL(source.src);
              });
              if ('poster' in slide && slide.poster) {
                URL.revokeObjectURL(slide.poster);
              }
            }
          });
          setSlides([]);
        }}
        slides={slides}
        index={currentIndex}
        plugins={[Fullscreen, Video, Zoom]}
      />

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
