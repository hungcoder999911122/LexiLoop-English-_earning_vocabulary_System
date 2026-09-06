package com.example.englishvocab.entity;

import jakarta.persistence.*;
import lombok.Getter;
import lombok.Setter;

import java.time.LocalDateTime;

@Getter
@Setter
@Entity
@Table(name = "Study_Sessions")
public class StudyHistory {
    @Id
    @GeneratedValue(strategy = GenerationType.IDENTITY)
    private Integer sessionID;

    @ManyToOne(optional = false)
    @JoinColumn(name = "userID")
    private User user;

    @ManyToOne
    @JoinColumn(name = "topicID")
    private Topic topic;

    @Column(name = "study_mode", nullable = false)
    private String studyMode;

    @Column(name = "started_at", insertable = false, updatable = false)
    private LocalDateTime startedAt;

    @Column(name = "ended_at")
    private LocalDateTime endedAt;

    @Column(name = "items_studied")
    private Integer itemsStudied = 0;

    @Column(name = "correct_count")
    private Integer correctCount = 0;

    @Column(name = "wrong_count")
    private Integer wrongCount = 0;
}
